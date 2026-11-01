# Dibuja el diagrama de Ishikawa (causa-efecto) del problema de la Clínica Vida Salud.
# Uso: python ishikawa.py  -> 04a_ishikawa_espina.png, 04b_ishikawa_categorias.png,
#                             04_ishikawa.png (completo) y 04c_arbol_problemas.png
from pathlib import Path

import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt

EFECTO = 'Programación deficiente\nde las citas médicas en la\nClínica Vida Salud'
ARRIBA = [
    ('C1. Métodos y procesos', ['Citas anotadas en cuadernos', 'Reprogramación solo por teléfono',
                                'Sin control de estados de la cita', 'Cancelaciones no registradas']),
    ('C2. Personal', ['Recepción sobrecargada en horas pico', 'Errores al transcribir datos',
                      'Desconoce cambios de horario', 'Sin roles ni permisos definidos']),
    ('C3. Tecnología', ['Hojas de cálculo aisladas', 'Sin sistema centralizado',
                        'Sin respaldo de la información', 'Sin acceso desde otro lugar']),
]
ABAJO = [
    ('C4. Información y datos', ['Pacientes duplicados o incompletos', 'Historial clínico disperso',
                                 'Sin reportes de atenciones', 'Sin trazabilidad de cambios']),
    ('C5. Pacientes', ['No conocen los horarios libres', 'Inasistencias sin aviso',
                       'Largas esperas al teléfono', 'No reciben recordatorios']),
    ('C6. Gestión administrativa', ['Horarios de médicos cambian sin aviso', 'Sin indicadores de gestión',
                                    'Decisiones sin datos', 'Control manual de feriados y permisos']),
]

TEAL, GRIS, AMBAR = '#0f766e', '#334155', '#b45309'
plt.rcParams['font.family'] = 'Segoe UI'
XS = [4.4, 9.0, 13.6]
Y = 4.1


def ishikawa(archivo, categorias=True, causas=True):
    fig, ax = plt.subplots(figsize=(19.5, 8.2), dpi=150)
    ax.set_xlim(0, 19.5)
    ax.set_ylim(0, 8.2)
    ax.axis('off')
    ax.annotate('', xy=(15.0, Y), xytext=(0.4, Y), arrowprops=dict(arrowstyle='-|>', lw=3, color=GRIS, mutation_scale=25))
    ax.text(17.2, Y, EFECTO, ha='center', va='center', fontsize=15, fontweight='bold', color='white',
            bbox=dict(boxstyle='round,pad=0.7', fc=TEAL, ec=TEAL))
    ax.text(9.5, 8.0, 'Diagrama de Ishikawa · causa y efecto', ha='center', fontsize=15, fontweight='bold', color=GRIS)
    if categorias:
        for grupo, signo in ((ARRIBA, 1), (ABAJO, -1)):
            for (cat, lista), x in zip(grupo, XS):
                y_ext = Y + signo * 3.2
                x_ext = x - 1.6
                ax.plot([x_ext, x], [y_ext, Y], color=GRIS, lw=2)
                ax.text(x_ext, y_ext + signo * 0.32, cat, ha='center', va='center', fontsize=13.5, fontweight='bold', color=AMBAR,
                        bbox=dict(boxstyle='round,pad=0.35', fc='#fffbeb', ec=AMBAR))
                if not causas:
                    continue
                for k, causa in enumerate(lista):
                    t = (k + 0.7) / (len(lista) + 0.4)
                    px = x_ext + (x - x_ext) * t
                    py = y_ext + (Y - y_ext) * t
                    ax.plot([px - 0.6, px], [py, py], color='#94a3b8', lw=1.1)
                    ax.text(px - 0.65, py + 0.08, causa, ha='right', va='bottom', fontsize=11, color='#1e293b')
    plt.tight_layout()
    plt.savefig(Path(__file__).with_name(archivo), facecolor='white')
    plt.close(fig)
    print(archivo)


def arbol(archivo):
    """Árbol de problemas: el problema raíz (PF1) causa los demás y todos llevan al efecto."""
    fig, ax = plt.subplots(figsize=(16, 7.2), dpi=150)
    ax.set_xlim(0, 16)
    ax.set_ylim(0, 7.2)
    ax.axis('off')
    caja = lambda x, y, txt, fc, ec, color='#1e293b', fs=11.5: ax.text(
        x, y, txt, ha='center', va='center', fontsize=fs, color=color, fontweight='bold',
        bbox=dict(boxstyle='round,pad=0.55', fc=fc, ec=ec, lw=1.4))
    flecha = lambda x1, y1, x2, y2: ax.annotate('', xy=(x2, y2), xytext=(x1, y1),
                                                arrowprops=dict(arrowstyle='-|>', color='#475569', lw=1.3))
    caja(8, 6.5, 'Programación deficiente de las citas\nmédicas en la Clínica Vida Salud', TEAL, TEAL, 'white', 14)
    medio = [('PF2', 'Citas\nsuperpuestas'), ('PF3', 'Cancelaciones e\ninasistencias\nsin control'),
             ('PF4', 'Datos duplicados\ne historial\ndisperso'), ('PF5', 'Sin reportes\npara la\nadministración'),
             ('PF6', 'Cambios de\nhorario no\ncomunicados'), ('PF7', 'Sin control\nde acceso'),
             ('PF8', 'Pacientes sin\nreserva en\nlínea')]
    xs = [1.2 + i * 2.27 for i in range(len(medio))]
    for (cod, txt), x in zip(medio, xs):
        caja(x, 3.9, f'{cod}\n{txt}', '#fffbeb', AMBAR, fs=10.5)
        flecha(x, 4.5, 8 + (x - 8) * 0.35, 6.05)
    caja(8, 1.0, 'PF1 · Registro manual sin sistema ni respaldo (problema raíz)', '#fee2e2', '#b91c1c', '#7f1d1d', 12.5)
    for x in xs[:4]:
        flecha(8 + (x - 8) * 0.3, 1.3, x, 3.3)
    ax.text(15.8, 0.15, 'Las flechas van de la causa al efecto.', ha='right', fontsize=9.5, color='#64748b', style='italic')
    plt.tight_layout()
    plt.savefig(Path(__file__).with_name(archivo), facecolor='white')
    plt.close(fig)
    print(archivo)


ishikawa('04a_ishikawa_espina.png', categorias=False)
ishikawa('04b_ishikawa_categorias.png', causas=False)
ishikawa('04_ishikawa.png')
arbol('04c_arbol_problemas.png')
