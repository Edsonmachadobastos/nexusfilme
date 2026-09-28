<?php
include "session.php";
include "header.php";
require_once __DIR__ . '/includes/planos_helpers.php';

$pwb_config = pwb_load_config();
$sportsApiUrl = $pwb_config['sports_api_url'];

// Data de hoje por extenso, em pt-BR, sem depender de extensões extras do PHP.
$diasSemana = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
$meses = [1=>'janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
$hoje = new DateTime('now');
$dataExtenso = $diasSemana[(int)$hoje->format('w')] . ', ' . (int)$hoje->format('j') . ' de ' . $meses[(int)$hoje->format('n')];
?>

<style>
    .jogos-wrap {
        padding: 28px 32px 60px;
        max-width: 1700px;
        margin: 0 auto;
    }

    /* ===== Hero ===== */
    .jogos-hero {
        position: relative;
        overflow: hidden;
        border-radius: 22px;
        padding: 34px 36px;
        margin-bottom: 26px;
        background:
            radial-gradient(900px 260px at 8% -20%, rgba(0,168,225,.28), transparent 60%),
            radial-gradient(700px 240px at 105% 120%, rgba(255,0,87,.22), transparent 60%),
            linear-gradient(135deg, #141b29 0%, #0d1420 100%);
        border: 1px solid rgba(255,255,255,0.08);
        box-shadow: 0 18px 50px -20px rgba(0,0,0,.6);
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 24px;
        flex-wrap: wrap;
    }
    .jogos-hero::after {
        content: "";
        position: absolute; inset: 0;
        background: repeating-linear-gradient(115deg, rgba(255,255,255,.025) 0 2px, transparent 2px 26px);
        pointer-events: none;
    }
    .jogos-hero-left { position: relative; z-index: 1; }
    .jogos-hero-icon {
        display: inline-flex; align-items: center; justify-content: center;
        width: 52px; height: 52px; border-radius: 15px; margin-bottom: 14px;
        background: linear-gradient(135deg, var(--accent), var(--cta));
        box-shadow: 0 10px 26px -8px rgba(0,168,225,.6);
        font-size: 1.3rem; color: #fff;
    }
    .jogos-hero h1 {
        font-size: 2rem;
        margin: 0 0 6px;
        color: #fff;
        font-weight: 800;
        letter-spacing: -.01em;
    }
    .jogos-hero p {
        color: #9aa4b8;
        margin: 0;
        font-size: .98rem;
        max-width: 520px;
    }
    .jogos-hero-date {
        position: relative; z-index: 1;
        display: flex; align-items: center; gap: 12px;
        background: rgba(255,255,255,.05);
        border: 1px solid rgba(255,255,255,.1);
        border-radius: 14px;
        padding: 12px 20px;
        color: #fff;
        white-space: nowrap;
    }
    .jogos-hero-date i { color: var(--accent); font-size: 1.05rem; }
    .jogos-hero-date .live-dot {
        width: 8px; height: 8px; border-radius: 50%; background: var(--cta);
        box-shadow: 0 0 0 0 rgba(255,0,87,.6);
        animation: jogosPulse 1.6s infinite;
        margin-right: 2px;
    }
    @keyframes jogosPulse {
        0%   { box-shadow: 0 0 0 0 rgba(255,0,87,.55); }
        70%  { box-shadow: 0 0 0 9px rgba(255,0,87,0); }
        100% { box-shadow: 0 0 0 0 rgba(255,0,87,0); }
    }
    .jogos-hero-date-label { font-size: .78rem; color: #9aa4b8; text-transform: uppercase; letter-spacing: .08em; }
    .jogos-hero-date-value { font-size: .98rem; font-weight: 700; }

    /* ===== Frame ===== */
    .jogos-frame-container {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 20px 60px -20px rgba(0,0,0,.55);
        border: 1px solid rgba(255,255,255,0.08);
        background: #0b1220;
    }
    .jogos-frame-container iframe {
        width: 100%;
        height: 78vh;
        min-height: 640px;
        border: 0;
        display: block;
        background: #0b1220;
        position: relative;
        z-index: 1;
    }

    /* Skeleton de carregamento, some assim que o iframe termina de carregar */
    .jogos-skeleton {
        position: absolute; inset: 0; z-index: 2;
        display: flex; flex-direction: column; gap: 16px;
        padding: 26px;
        background: #0b1220;
    }
    .jogos-skeleton .sk-row { display: flex; gap: 16px; }
    .jogos-skeleton .sk-card {
        flex: 1; height: 168px; border-radius: 16px;
        background: linear-gradient(100deg, #121a2a 30%, #1a2436 50%, #121a2a 70%);
        background-size: 220% 100%;
        animation: jogosShimmer 1.3s ease-in-out infinite;
    }
    @keyframes jogosShimmer {
        0% { background-position: 200% 0; }
        100% { background-position: -20% 0; }
    }
    .jogos-frame-container.is-loaded .jogos-skeleton { display: none; }

    .jogos-empty {
        max-width: 560px;
        margin: 40px auto 0;
        text-align: center;
        color: #9aa4b8;
        padding: 54px 30px;
        border-radius: 20px;
        border: 1px dashed rgba(255,255,255,.14);
        background: rgba(255,255,255,.02);
    }
    .jogos-empty i {
        display: inline-flex; align-items: center; justify-content: center;
        width: 56px; height: 56px; border-radius: 16px; margin-bottom: 16px;
        background: rgba(255,0,87,.12); color: var(--cta); font-size: 1.5rem;
    }
    .jogos-empty p { margin: 0; font-size: .95rem; line-height: 1.6; }

    @media (max-width: 900px) {
        .jogos-wrap { padding: 18px 14px 44px; }
        .jogos-hero { padding: 24px 22px; }
        .jogos-hero h1 { font-size: 1.5rem; }
        .jogos-frame-container iframe { height: 70vh; min-height: 480px; }
    }
</style>

<div class="jogos-wrap">
    <div class="jogos-hero">
        <div class="jogos-hero-left">
            <div class="jogos-hero-icon"><i class="fas fa-futbol"></i></div>
            <h1>Jogos do Dia</h1>
            <p>Acompanhe os jogos e horários de transmissão de hoje, com o canal de cada partida.</p>
        </div>
        <div class="jogos-hero-date">
            <span class="live-dot"></span>
            <i class="fas fa-calendar-day"></i>
            <div>
                <div class="jogos-hero-date-label">Hoje</div>
                <div class="jogos-hero-date-value"><?php echo htmlspecialchars($dataExtenso); ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($sportsApiUrl)): ?>
        <div class="jogos-frame-container" id="jogosFrameContainer">
            <div class="jogos-skeleton">
                <div class="sk-row"><div class="sk-card"></div><div class="sk-card"></div><div class="sk-card"></div></div>
                <div class="sk-row"><div class="sk-card"></div><div class="sk-card"></div><div class="sk-card"></div></div>
            </div>
            <iframe src="<?php echo htmlspecialchars($sportsApiUrl); ?>" loading="lazy" title="Jogos do Dia"
                onload="document.getElementById('jogosFrameContainer').classList.add('is-loaded')"></iframe>
        </div>
    <?php else: ?>
        <div class="jogos-empty">
            <i class="fas fa-triangle-exclamation"></i>
            <p>A lista de jogos ainda não foi configurada. Fale com o administrador do painel.</p>
        </div>
    <?php endif; ?>
</div>

