<?php
/**
 * Página pública: portada, especialidades, médicos y cómo reservar.
 * @var array $especialidades @var array $medicos @var array $cifras
 */
$titulo = 'Inicio';
$u = Auth::usuario();
// Ícono según la especialidad (las que se agreguen después usan la cruz)
$iconoEsp = function (string $n): string {
    $n = strtr(mb_strtolower($n), ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u']);
    foreach (['cardio' => 'pulso', 'pediat' => 'usuario', 'odont' => 'brillo', 'dermat' => 'gota', 'gineco' => 'usuario-ok', 'general' => 'estetoscopio'] as $clave => $icono) {
        if (str_contains($n, $clave)) {
            return $icono;
        }
    }
    return 'cruz';
};
$iniciales = fn(array $m) => mb_strtoupper(mb_substr($m['nombres'], 0, 1) . mb_substr($m['apellidos'], 0, 1));
?>
<main>
    <!-- Portada -->
    <section class="pub-hero">
        <div class="blob" style="width:460px;height:460px;background:#0d9488;top:-160px;left:-120px"></div>
        <div class="blob" style="width:380px;height:380px;background:#4f46e5;bottom:-160px;right:-60px"></div>
        <div class="pub-wrap pub-hero-in">
            <div>
                <span class="pub-etiqueta anim"><?= icono('calendario-ok', 14) ?> Reserva de citas en línea</span>
                <h1 class="anim" style="--i:1">Tu salud, <em>sin filas</em> y sin esperas al teléfono.</h1>
                <p class="anim" style="--i:2">Elige la especialidad y el médico, mira sus horarios libres y reserva tu cita en minutos. Después consulta tu historial y los resultados de tus estudios desde el celular.</p>
                <div class="pub-cta anim" style="--i:3">
                    <a href="<?= e(PublicoController::enlaceReserva()) ?>" class="btn btn-primary btn-lg"><?= icono('calendario-mas', 18) ?> Reservar una cita</a>
                    <a href="#especialidades" class="btn btn-outline btn-lg">Ver especialidades</a>
                </div>
                <?php if (!$u): ?>
                    <p class="pub-nota anim" style="--i:4">¿Primera vez? <a href="/registro">Crea tu cuenta de paciente</a> en un minuto. ¿Ya tienes cuenta? <a href="/login">Ingresa aquí</a>.</p>
                <?php endif; ?>
            </div>
            <div class="pub-cifras anim" style="--i:3">
                <div><b><?= (int) $cifras['especialidades'] ?></b><span>especialidades</span></div>
                <div><b><?= (int) $cifras['medicos'] ?></b><span>médicos</span></div>
                <div><b><?= number_format((int) $cifras['atendidas'], 0, ',', '.') ?></b><span>citas atendidas</span></div>
                <div><b><?= (int) config('DURACION_CITA_MINUTOS', '30') ?> min</b><span>por consulta</span></div>
            </div>
        </div>
    </section>

    <!-- Especialidades -->
    <section class="pub-seccion" id="especialidades">
        <div class="pub-wrap">
            <div class="pub-titulo">
                <span class="pub-etiqueta"><?= icono('estetoscopio', 14) ?> Especialidades</span>
                <h2>¿Qué especialidad necesitas?</h2>
                <p>Atendemos en <?= count($especialidades) ?> especialidades. Elige una para ver los médicos y reservar.</p>
            </div>
            <div class="pub-grid">
                <?php foreach ($especialidades as $i => $esp): ?>
                    <article class="pub-card anim" style="--i:<?= $i ?>">
                        <div class="pub-icono"><?= icono($iconoEsp($esp['nombre']), 22) ?></div>
                        <h3><?= e($esp['nombre']) ?></h3>
                        <p><?= e($esp['descripcion'] ?: 'Consulta especializada.') ?></p>
                        <div class="pub-card-pie">
                            <span class="faint"><?= (int) $esp['medicos'] ?> médico<?= (int) $esp['medicos'] === 1 ? '' : 's' ?></span>
                            <?php if ((int) $esp['medicos'] > 0): ?>
                                <a href="<?= e(PublicoController::enlaceReserva((int) $esp['idespecialidad'])) ?>" class="pub-enlace">Reservar <?= icono('derecha', 14) ?></a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Médicos -->
    <section class="pub-seccion pub-alterna" id="medicos">
        <div class="pub-wrap">
            <div class="pub-titulo">
                <span class="pub-etiqueta"><?= icono('usuarios', 14) ?> Nuestro equipo</span>
                <h2>Conoce a nuestros médicos</h2>
                <p>Cada médico atiende en sus días y horarios. Al reservar, el sistema solo te muestra las horas que siguen libres.</p>
            </div>
            <div class="pub-grid">
                <?php foreach ($medicos as $i => $m): ?>
                    <article class="pub-card pub-medico anim" style="--i:<?= $i ?>">
                        <div class="pub-medico-cab">
                            <div class="pub-avatar" aria-hidden="true"><?= e($iniciales($m)) ?></div>
                            <div>
                                <h3><?= e($m['titulo'] . ' ' . $m['nombres'] . ' ' . $m['apellidos']) ?></h3>
                                <div class="pub-chips">
                                    <?php foreach (explode(', ', $m['especialidades']) as $esp): ?><span class="pub-chip"><?= e($esp) ?></span><?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <ul class="pub-horario">
                            <?php foreach ($m['horario'] ?: ['Horario por confirmar'] as $linea): ?>
                                <li><?= icono('reloj', 14) ?> <?= e($linea) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?= e(PublicoController::enlaceReserva((int) $m['idespecialidad'], (int) $m['idmedico'])) ?>" class="btn btn-outline pub-ancho"><?= icono('calendario-mas', 16) ?> Reservar con <?= e($m['titulo']) ?> <?= e(explode(' ', $m['apellidos'])[0]) ?></a>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Cómo reservar -->
    <section class="pub-seccion" id="como-reservar">
        <div class="pub-wrap">
            <div class="pub-titulo">
                <span class="pub-etiqueta"><?= icono('lista', 14) ?> Cómo reservar</span>
                <h2>Tu cita en cuatro pasos</h2>
            </div>
            <ol class="pub-pasos">
                <?php foreach ([
                    ['usuario-mas', 'Crea tu cuenta o ingresa', 'Regístrate como paciente con tu CI. Si ya tienes cuenta, inicia sesión.'],
                    ['estetoscopio', 'Elige especialidad y médico', 'Revisa los médicos de cada especialidad y sus días de atención.'],
                    ['calendario', 'Escoge día y hora libres', 'Solo verás horarios disponibles: nunca se cruzan dos citas.'],
                    ['campana', 'Recibe tu recordatorio', 'Tu cita aparece en el portal con su recordatorio; puedes cancelarla con anticipación.'],
                ] as $i => [$ic, $t, $d]): ?>
                    <li class="anim" style="--i:<?= $i ?>"><div class="pub-icono"><?= icono($ic, 20) ?></div><h3><?= e($t) ?></h3><p><?= e($d) ?></p></li>
                <?php endforeach; ?>
            </ol>
            <div class="pub-final">
                <div>
                    <h3>¿Listo para reservar?</h3>
                    <p><?= $u ? 'Continúa desde tu cuenta.' : 'Para reservar necesitas iniciar sesión como paciente; si no tienes cuenta, créala gratis.' ?></p>
                </div>
                <div class="pub-cta">
                    <a href="<?= e(PublicoController::enlaceReserva()) ?>" class="btn btn-primary btn-lg"><?= icono('calendario-mas', 18) ?> Reservar una cita</a>
                    <?php if (!$u): ?><a href="/registro" class="btn btn-outline btn-lg">Crear cuenta</a><?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</main>
