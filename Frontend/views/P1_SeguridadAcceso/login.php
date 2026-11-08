<?php
/** CU1 · Iniciar sesión */
$titulo = 'Ingresar';
?>
<style>
    /* Animaciones exclusivas del login */
    .ecg path { stroke-dasharray: 1200; stroke-dashoffset: 1200; animation: trazo 3s ease-in-out infinite; }
    @keyframes trazo { 0% { stroke-dashoffset: 1200; } 60%, 100% { stroke-dashoffset: 0; } }
    .auth-hero .blob { animation: flotar 14s ease-in-out infinite; }
    .auth-hero .blob.b2 { animation-duration: 11s; animation-direction: reverse; }
    .auth-hero .blob.b3 { animation: latir 9s ease-in-out infinite; }
    @keyframes latir { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.2); } }
    .hero-card { transition: transform .3s cubic-bezier(.16,1,.3,1); }
    .hero-card:hover { transform: translateY(-4px); }
    .shake { animation: shake .4s; }
    @keyframes shake { 20%, 60% { transform: translateX(-8px); } 40%, 80% { transform: translateX(8px); } }
</style>
<div class="auth">
    <section class="auth-hero">
        <div class="grid-bg"></div>
        <div class="blob" style="width:420px;height:420px;background:#0d9488;top:-120px;left:-100px"></div>
        <div class="blob b2" style="width:380px;height:380px;background:#4f46e5;bottom:-120px;right:-80px"></div>
        <div class="blob b3" style="width:260px;height:260px;background:#0ea5e9;top:40%;left:45%;opacity:.4"></div>

        <div class="brand anim" style="padding:0;--i:0">
            <div class="brand-logo"><?= icono('cruz', 22) ?></div>
            <div><strong style="color:#fff">Nova Salud</strong><small style="color:rgba(255,255,255,.6)"><?= e(config('NOMBRE_CLINICA', 'Clínica Vida Salud')) ?></small></div>
        </div>

        <div>
            <h2 class="anim" style="--i:1">La gestión de tu clínica, <em>en un solo lugar.</em></h2>
            <p class="anim" style="--i:2">Pacientes, médicos, horarios y citas conectados: sin cruces de horario, sin filas y con el historial siempre a mano.</p>
            <svg class="ecg anim" style="--i:3" viewBox="0 0 560 70" aria-hidden="true">
                <path d="M0 35 H120 L135 35 L145 10 L158 60 L170 20 L180 35 H260 L272 35 L280 25 L290 35 H380 L395 35 L405 5 L418 65 L430 18 L440 35 H560"/>
            </svg>
        </div>

        <div class="hero-cards">
            <?php foreach ([['calendario-ok', 'Citas en línea', 'Disponibilidad real'], ['estetoscopio', 'Agenda médica', 'Por médico y fecha'], ['escudo', 'Acceso seguro', 'Roles y bitácora']] as $i => [$ic, $t, $s]): ?>
                <div class="hero-card anim" style="--i:<?= 4 + $i ?>"><?= icono($ic, 22) ?><strong><?= e($t) ?></strong><span><?= e($s) ?></span></div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="auth-side">
        <div class="auth-box anim">
            <a href="/" class="faint" style="display:inline-flex;align-items:center;gap:6px;font-size:14px;font-weight:600;margin-bottom:18px"><?= icono('izquierda', 14) ?> Volver al inicio</a>
            <div class="brand" style="padding:0">
                <div class="brand-logo"><?= icono('cruz', 22) ?></div>
                <div><strong>Nova Salud</strong><small>Sistema de gestión de citas médicas</small></div>
            </div>
            <h1>Bienvenido de nuevo</h1>
            <p>Ingrese con su usuario o correo electrónico y su contraseña.</p>

            <form method="post" action="/login" class="auth-form" data-validar>
                <?= csrf_field() ?>
                <label class="field">
                    <span>Usuario o correo electrónico</span>
                    <span class="input-icon">
                        <?= icono('usuario') ?>
                        <input class="input<?= clase_error('login') ?>" name="login" value="<?= e(old('login')) ?>" required maxlength="150"
                               autocomplete="username" autofocus placeholder="Ej. admin o nombre@correo.com">
                    </span>
                    <?= error_campo('login') ?>
                </label>
                <label class="field">
                    <span>Contraseña</span>
                    <span class="input-icon">
                        <?= icono('candado') ?>
                        <input class="input<?= clase_error('password') ?>" type="password" name="password" id="clave" required maxlength="100"
                               autocomplete="current-password" placeholder="••••••••" style="padding-right:48px">
                        <button type="button" class="btn btn-ghost btn-icon btn-sm trail" data-ver-clave="clave" aria-label="Mostrar contraseña" style="width:34px;height:34px"><?= icono('ver', 17) ?></button>
                    </span>
                    <?= error_campo('password') ?>
                </label>
                <div style="text-align:right;margin-top:-8px"><a href="/recuperar" style="font-size:13.5px;font-weight:600">¿Olvidaste tu contraseña?</a></div>
                <button type="submit" class="btn btn-primary btn-block" style="height:48px;font-size:15px">Ingresar <?= icono('flecha', 17) ?></button>
            </form>

            <div class="demo">
                ¿Es paciente y aún no tiene cuenta? <a href="/registro"><strong>Regístrese aquí</strong></a> para reservar sus citas en línea.
            </div>
        </div>
    </section>
</div>
