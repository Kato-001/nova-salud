# Crea el modelo UML de NovaSol Salud en Enterprise Architect (NovaSol_Salud.qea)
# a partir de modelo.json (generado por extraer_modelo.py), usando la API COM de EA.
# Uso: powershell -ExecutionPolicy Bypass -File crear_modelo_ea.ps1
$ErrorActionPreference = 'Stop'
$dir = Split-Path -Parent $MyInvocation.MyCommand.Path
$m = Get-Content (Join-Path $dir 'modelo.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$archivo = Join-Path $dir 'NovaSol_Salud.qea'
$base = Get-ChildItem 'C:\Program Files\Sparx Systems\*\EABase.qea' | Select-Object -First 1
Copy-Item $base.FullName $archivo -Force

$r = New-Object -ComObject EA.Repository
if (-not $r.OpenFile($archivo)) { throw "No se pudo abrir $archivo" }
$proy = $r.GetProjectInterface()

function Guardar($o) { [void]$o.Update(); $o }
function Paquete($padre, $nombre, $notas = '') {
    $p = $padre.Packages.AddNew($nombre, 'Package'); $p.Notes = $notas; [void]$p.Update(); $padre.Packages.Refresh(); $p
}
function Elemento($paquete, $nombre, $tipo, $estereotipo = '') {
    $e = $paquete.Elements.AddNew($nombre, $tipo); if ($estereotipo) { $e.Stereotype = $estereotipo }; [void]$e.Update(); $e
}
function Conector($desde, $hacia, $tipo, $estereotipo = '', $nombre = '') {
    $c = $desde.Connectors.AddNew($nombre, $tipo); $c.SupplierID = $hacia.ElementID
    if ($estereotipo) { $c.Stereotype = $estereotipo }
    [void]$c.Update(); $c
}
$script:diagramas = New-Object System.Collections.ArrayList
function Nuevo($paquete, $nombre, $tipo) {
    $d = $paquete.Diagrams.AddNew($nombre, $tipo)
    [void]$d.Update(); [void]$script:diagramas.Add($d.DiagramGUID); $d
}
function Poner($d, $e, $l, $t, $ancho, $alto) {
    $o = $d.DiagramObjects.AddNew("l=$l;r=$($l + $ancho);t=-$t;b=-$($t + $alto)", ''); $o.ElementID = $e.ElementID; [void]$o.Update()
}
# Diagrama con distribución automática de EA (clases y paquetes)
function Diagrama($paquete, $nombre, $tipo, $elementos) {
    $d = Nuevo $paquete $nombre $tipo
    $i = 0
    foreach ($e in $elementos) { Poner $d $e (20 + ($i % 5) * 230) (20 + [math]::Floor($i / 5) * 200) 160 90; $i++ }
    [void]$d.Update()
    [void]$proy.LayoutDiagramEx($d.DiagramGUID, 0, 4, 60, 60, $true)
    $d
}
# Diagrama de casos de uso: actores a la izquierda y casos de uso en dos columnas
function DiagramaCU($paquete, $nombre, $actores, $casos) {
    $d = Nuevo $paquete $nombre 'Use Case'
    $cols = if ($casos.Count -le 7) { 1 } else { 2 }   # una columna cuando caben: menos líneas cruzadas
    $filas = [math]::Ceiling($casos.Count / $cols)
    # Cada actor necesita unos 130 px (figura y nombre); el diagrama crece si hace falta
    $paso = [math]::Max(90, [math]::Ceiling(130 * $actores.Count / [math]::Max($filas, 1)))
    $altoTotal = [math]::Max($filas * $paso, 130 * $actores.Count)
    $i = 0
    foreach ($a in $actores) {
        $t = 40 + [math]::Floor(($i + 0.5) * $altoTotal / [math]::Max($actores.Count, 1)) - 45
        Poner $d $a 40 $t 50 90; $i++
    }
    $i = 0
    foreach ($c in $casos) { Poner $d $c (340 + ($i % $cols) * 300) (40 + [math]::Floor($i / $cols) * $paso) 220 60; $i++ }
    [void]$d.Update()
    $d
}
$proj = $proy

$raiz = $r.Models.GetAt(0); $raiz.Name = 'NovaSol Salud'; [void]$raiz.Update()

# ---------------------------------------------------------------- 1. Modelo de dominio
$pDom = Paquete $raiz '01 Modelo de dominio' 'Conceptos del negocio de la Clínica Vida Salud y sus relaciones.'
$dom = @{}
foreach ($c in $m.dominio.clases) {
    $e = Elemento $pDom $c.nombre 'Class'
    foreach ($a in $c.atributos) { $at = $e.Attributes.AddNew($a.nombre, $a.tipo); [void]$at.Update() }
    $dom[$c.nombre] = $e
}
foreach ($rl in $m.dominio.relaciones) {
    $c = Conector $dom[$rl.a] $dom[$rl.b] 'Association' '' $rl.nombre
    $c.ClientEnd.Cardinality = $rl.card_a; [void]$c.ClientEnd.Update()
    $c.SupplierEnd.Cardinality = $rl.card_b; [void]$c.SupplierEnd.Update(); [void]$c.Update()
}
[void](Diagrama $pDom 'Modelo de dominio' 'Logical' $dom.Values)

# ---------------------------------------------------------------- 2. Captura de requisitos
$pReq = Paquete $raiz '02 Captura de requisitos' 'Actores y casos de uso organizados por ciclo y por paquete.'
$pAct = Paquete $pReq 'Actores'
$act = @{}
foreach ($a in $m.actores) { $act[$a] = Elemento $pAct $a 'Actor' }
$usuario = Elemento $pAct 'Usuario' 'Actor'
foreach ($a in $m.actores) { [void](Conector $act[$a] $usuario 'Generalization') }

$cus = @{}
foreach ($ci in $m.ciclos) {
    $pCi = Paquete $pReq $ci.nombre
    $subp = @{}
    foreach ($cu in $ci.casos) {
        if (-not $subp.ContainsKey($cu.paquete)) { $subp[$cu.paquete] = Paquete $pCi $cu.paquete }
        $e = Elemento $subp[$cu.paquete] ("CU$($cu.id) $($cu.nombre)") 'UseCase'
        $e.Notes = "Caso de uso del $($ci.nombre), paquete $($cu.paquete)."; [void]$e.Update()
        $cus[[int]$cu.id] = $e
    }
    foreach ($u in $ci.usa) { [void](Conector $act[$u.actor] $cus[[int]$u.cu] 'Association') }
    foreach ($rl in $ci.relaciones) { [void](Conector $cus[[int]$rl.origen] $cus[[int]$rl.destino] 'Dependency' $rl.tipo) }
    # Actores del ciclo (sin repetir; se compara por nombre porque los objetos COM no se pueden comparar)
    $nombres = @($ci.usa | ForEach-Object { $_.actor } | Sort-Object -Unique)
    $actoresCiclo = @($m.actores | Where-Object { $nombres -contains $_ } | ForEach-Object { $act[$_] })
    $casosCiclo = @($ci.casos | ForEach-Object { $cus[[int]$_.id] })
    [void](DiagramaCU $pCi ("Casos de uso del $($ci.nombre)") $actoresCiclo $casosCiclo)
    # Un diagrama por paquete, más legible
    foreach ($np in $subp.Keys) {
        $ids = @($ci.casos | Where-Object { $_.paquete -eq $np } | ForEach-Object { [int]$_.id })
        $nomAct = @($ci.usa | Where-Object { $ids -contains [int]$_.cu } | ForEach-Object { $_.actor } | Sort-Object -Unique)
        $aP = @($m.actores | Where-Object { $nomAct -contains $_ } | ForEach-Object { $act[$_] })
        [void](DiagramaCU $subp[$np] ("$np") $aP @($ids | ForEach-Object { $cus[$_] }))
    }
}

# ---------------------------------------------------------------- 3. Análisis: paquetes
$pAna = Paquete $raiz '03 Análisis' 'Paquetes de análisis y sus dependencias.'
$paq = @{}
foreach ($n in $m.paquetes) { $p = Paquete $pAna $n; $paq[$n] = $p.Element }
foreach ($dp in $m.dependencias) { [void](Conector $paq[$dp.de] $paq[$dp.a] 'Dependency' 'use') }
[void](Diagrama $pAna 'Vista de paquetes de análisis' 'Package' $paq.Values)

# ---------------------------------------------------------------- 4. Diseño de datos
$pDat = Paquete $raiz '04 Diseño de datos' 'Clases persistentes (se mapean a las tablas del esquema clinica de PostgreSQL).'
$per = @{}
foreach ($c in $m.persistentes.clases) {
    $e = Elemento $pDat $c.nombre 'Class'
    foreach ($a in $c.atributos) { $at = $e.Attributes.AddNew($a.nombre, $a.tipo); [void]$at.Update() }
    $per[$c.nombre] = $e
}
foreach ($rl in $m.persistentes.relaciones) {
    if ($rl.composicion) {
        # Composición: el rombo va en el todo (a); la parte (b) es el origen
        $c = Conector $per[$rl.b] $per[$rl.a] 'Aggregation'; $c.Subtype = 'Strong'
        $c.ClientEnd.Cardinality = $rl.card_b; $c.SupplierEnd.Cardinality = $rl.card_a; $c.SupplierEnd.Aggregation = 2
    } else {
        $c = Conector $per[$rl.a] $per[$rl.b] 'Association'
        $c.ClientEnd.Cardinality = $rl.card_a; $c.SupplierEnd.Cardinality = $rl.card_b
    }
    [void]$c.ClientEnd.Update(); [void]$c.SupplierEnd.Update(); [void]$c.Update()
}
[void](Diagrama $pDat 'Diagrama de clases persistentes' 'Logical' $per.Values)

# Vista previa de los diagramas (la versión de prueba agrega marca de agua)
$prev = Join-Path $dir 'vista_previa'; New-Item -ItemType Directory -Force $prev | Out-Null
foreach ($g in $script:diagramas) {
    $d = $r.GetDiagramByGuid($g)
    [void]$proy.PutDiagramImageToFile($g, (Join-Path $prev ($d.Name + '.png')), 1)
}

"Elementos: " + ($dom.Count + $act.Count + 1 + $cus.Count + $paq.Count + $per.Count)
$r.CloseFile(); $r.Exit()
"Modelo creado: $archivo"
