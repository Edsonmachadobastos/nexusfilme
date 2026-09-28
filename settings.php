<?php
include "session.php";
$pagename = "Settings";
include "header.php";
include "config.php";

if (isset($_COOKIE["settings_array"])) {
    $SettingArray = json_decode($_COOKIE["settings_array"], true);
    $stream_type = $SettingArray["stream_type"];
} else {
    $stream_type = "m3u8";
}

// CSS ULTRA MODERNO E RESPONSIVO
echo "<style>
body {
    background: #000 !important;
    color: #f1f1f1;
    font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
    margin: 0;
    padding: 0;
    min-height: 100vh;
}
a { color: #fff; text-decoration: none; }
a:hover { color: #00ffe7; text-shadow: 0 0 8px #00ffe7; }

.topmoviestext2 {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 32px 0 0 0;
}
.topmoviestext2 .item img {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    box-shadow: 0 8px 32px 0 rgba(0,0,0,0.25);
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(4px);
    border: 3px solid #23283b;
}

/* ======= GLASSMORPHISM CARD ======= */
.setitem {
    background: rgba(30, 34, 48, 0.85);
    box-shadow: 0 8px 32px 0 rgba(0,0,0,0.37);
    border-radius: 32px;
    border: 1.5px solid rgba(255,255,255,0.14);
    margin: 32px auto 0 auto;
    width: 100%;
    max-width: 480px;
    padding: 48px 32px 32px 32px;
    text-align: center;
    position: relative;
    transition: box-shadow 0.3s;
}
.setitem:hover {
    box-shadow: 0 12px 48px 0 #00ffe733, 0 8px 32px 0 rgba(0,0,0,0.47);
}

/* ======= HEADER & ICON ======= */
.notification-heading {
    font-size: 2.1rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    margin-bottom: 18px;
    color: #fff;
    text-shadow: 0 2px 16px #00ffe7cc;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
}
.notification-heading .icon {
    font-size: 2.6rem;
    color: #00ffe7;
    filter: drop-shadow(0 0 8px #00ffe7aa);
}

/* ======= RADIO BUTTONS CUSTOM ======= */
.set_type {
    color: #e3e3e3;
    font-size: 1.2rem;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 18px;
}
.themecontainer {
    display: flex;
    align-items: center;
    position: relative;
    padding-left: 48px;
    margin-bottom: 0;
    font-size: 1.15rem;
    cursor: pointer;
    user-select: none;
    min-height: 44px;
    font-weight: 500;
    letter-spacing: 0.02em;
    color: #f1f1f1;
    transition: color 0.2s;
}
.themecontainer:hover {
    color: #00ffe7;
    text-shadow: 0 0 8px #00ffe788;
}
.themecontainer input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}
.themecontainer .checkmark {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    height: 36px;
    width: 36px;
    background: rgba(36, 39, 57, 0.85);
    border-radius: 50%;
    box-shadow: 0 4px 20px 1px rgba(0,0,0,0.09), 0 1px 4px rgba(0,0,0,0.07);
    border: 2px solid #00ffe7;
    transition: border-color 0.2s, box-shadow 0.2s;
    display: flex;
    align-items: center;
    justify-content: center;
}
.themecontainer:hover .checkmark {
    border-color: #fff;
    box-shadow: 0 0 12px #00ffe7cc;
}
.themecontainer input:checked ~ .checkmark {
    border: 2.5px solid #00ffe7;
    box-shadow: 0 0 18px #00ffe7cc;
    background: linear-gradient(135deg, #00ffe7 0%, #23283b 100%);
}
.checkmark:after {
    content: '';
    display: block;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: #fff;
    opacity: 0;
    transition: opacity 0.2s;
    margin: auto;
}
.themecontainer input:checked ~ .checkmark:after {
    opacity: 1;
    background: #00ffe7;
    box-shadow: 0 0 8px #00ffe7cc;
}

/* ======= BOTÃO ======= */
.button {
    width: 100%;
    max-width: 480px;
    margin: 24px auto 0 auto;
    text-align: center;
}
.btn.set_type {
    background: linear-gradient(90deg, #00ffe7 0%, #0d4d5f 100%);
    color: #181c24;
    font-size: 1.25rem;
    font-weight: 700;
    border: none;
    border-radius: 16px;
    padding: 18px 0;
    width: 100%;
    box-shadow: 0 2px 16px #00ffe799;
    cursor: pointer;
    transition: background 0.2s, color 0.2s, box-shadow 0.2s;
    letter-spacing: 0.03em;
    outline: none;
}
.btn.set_type:hover, .btn.set_type:focus {
    background: linear-gradient(90deg, #0d4d5f 0%, #00ffe7 100%);
    color: #fff;
    box-shadow: 0 4px 32px #00ffe7cc;
}

/* ======= RESPONSIVO ======= */
@media (max-width: 767px) {
    .setitem, .button {
        max-width: 98vw;
        width: 98vw;
        padding: 24px 6vw 18px 6vw;
        box-sizing: border-box;
        margin: 18px auto 0 auto;
    }
    .topmoviestext2 .item img {
        width: 60px;
        height: 60px;
    }
    .notification-heading {
        font-size: 1.05rem;
        gap: 7px;
    }
    .notification-heading .icon {
        font-size: 1.4rem;
    }
    .themecontainer {
        font-size: 0.98rem;
        padding-left: 42px;
    }
    .themecontainer .checkmark {
        height: 26px;
        width: 26px;
    }
}
</style>";

echo '<!--section topmoviestext start-->
<div class="topmoviestext2">
    <div class="item">
        <img src="/assets/images/adminimg.png" alt="Configurações">
    </div>
</div>';

echo '<form action="setting/save.php" method="POST" autocomplete="off" spellcheck="false">
<div class="icon_sec">
    <div class="setitem glass">
        <h4 class="notification-heading"><i class="fa fa-play-circle icon" aria-hidden="true"></i> Formatos de transmissão</h4>
        <p class="set_type">
            <label class="themecontainer">MPEG(ts) 
                <input name="stream_format" id="stream_format_ts" type="radio" class="set_type" value="ts" ' . ($stream_type == "ts" ? 'checked' : '') . '>
                <span class="checkmark"></span>
            </label>
            <label class="themecontainer">HLS(m3u8)
                <input name="stream_format" id="stream_format_m3u8" type="radio" class="set_type" value="m3u8" ' . ($stream_type == "m3u8" ? 'checked' : '') . '>
                <span class="checkmark"></span>
            </label>
        </p>
    </div>
</div>';

echo '<div class="button">
    <input name="submit" type="submit" class="btn btn-danger set_type" value="Salvar configurações">
</div>
</form>';

echo '<script>
$(function() {
    $("#settings_nav").addClass("active_menu");
});
</script>';
?>
