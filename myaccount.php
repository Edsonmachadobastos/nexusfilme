<?php

include "session.php";
$pagename = "My Account";
include "header.php";
include "config.php";

// cURL em vez de file_get_contents: não depende de allow_url_fopen (que em
// muitas hospedagens compartilhadas vem desligado por padrão).
$ch = curl_init($get_dns . "/player_api.php?username=" . $username . "&password=" . $password);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
]);
$panel_api = curl_exec($ch);
curl_close($ch);
$data = json_decode($panel_api, true);
$channel_api2 = json_decode(json_encode($data["user_info"] ?? []), true);
$exp = $channel_api2["exp_date"] ?? null;
$acc_active = $channel_api2["status"] ?? null;
$acc_user = $channel_api2["username"] ?? null;

if ($exp == NULL) {
    $expire = "UNLIMITED";
} else {
    $expire = gmdate("d/m/Y", $exp);
}

// CSS ULTRA MODERNO E RESPONSIVO
echo '<style>
body {
    background: #000 !important;
    color: #f1f1f1;
    font-family: "Inter", "Segoe UI", Arial, sans-serif;
    margin: 0;
    padding: 0;
    min-height: 100vh;
}
.account-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    min-height: 100vh;
    padding: 0;
}
.topmoviestext2 {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 32px;
    margin: 0 0 28px 0;
    width: 100%;
    flex-wrap: wrap;
}
.topmoviestext2 .item {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.topmoviestext2 .item img {
    width: 110px;
    height: 110px;
    border-radius: 50%;
    box-shadow: 0 8px 32px 0 rgba(0,0,0,0.25);
    background: rgba(255,255,255,0.05);
    backdrop-filter: blur(4px);
    border: 3px solid #23283b;
    margin-top: 8px;
}
.notification-heading {
    font-size: 2.1rem;
    font-weight: 700;
    letter-spacing: 0.01em;
    margin-bottom: 8px;
    color: #fff;
    text-shadow: 0 2px 16px #00ffe7cc;
}
.notification-text {
    font-size: 1.13rem;
    color: #b3eaff;
    margin-bottom: 0;
    text-align: center;
    max-width: 350px;
    text-shadow: 0 1px 8px #00ffe722;
}
.acc_id_sec {
    background: rgba(30, 34, 48, 0.85);
    box-shadow: 0 8px 32px 0 rgba(0,0,0,0.37);
    border-radius: 32px;
    border: 1.5px solid rgba(255,255,255,0.14);
    padding: 36px 38px 24px 38px;
    margin: 0 auto 32px auto;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    min-width: 260px;
    max-width: 410px;
    width: 100%;
    gap: 10px;
    transition: box-shadow 0.3s;
    position: relative;
}
.acc_id_sec:hover {
    box-shadow: 0 12px 48px 0 #00ffe733, 0 8px 32px 0 rgba(0,0,0,0.47);
}
.acc_id_sec .item {
    padding: 0;
    margin-bottom: 18px;
    width: 100%;
}
.acc_id_sec .item:last-child {
    margin-bottom: 0;
}
.acc_id_sec p {
    font-size: 1.15rem;
    color: #f1f1f1;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 6px;
    letter-spacing: 0.02em;
}
.acc_id_sec strong {
    color: #00ffe7;
    font-weight: 600;
    letter-spacing: 0.03em;
    font-size: 1.1rem;
    text-shadow: 0 0 8px #00ffe7aa;
}

/* Status visual */
.acc_id_sec .status {
    font-weight: 700;
    padding: 3px 14px;
    border-radius: 12px;
    margin-left: 8px;
    font-size: 1rem;
    letter-spacing: 0.02em;
    background: linear-gradient(90deg, #00ffe7 0%, #0d4d5f 100%);
    color: #181c24;
    box-shadow: 0 1px 8px #00ffe799;
    display: inline-block;
}
.acc_id_sec .status.inactive {
    background: linear-gradient(90deg, #ff0055 0%, #23283b 100%);
    color: #fff;
    box-shadow: 0 1px 8px #ff005599;
}

/* Responsivo */
@media (max-width: 767px) {
    .account-container {
        padding: 0;
        min-height: unset;
    }
    .topmoviestext2 {
        flex-direction: column;
        gap: 10px;
        padding: 0 0 8px 0;
    }
    .topmoviestext2 .item img {
        width: 70px;
        height: 70px;
    }
    .notification-heading {
        font-size: 1.25rem;
    }
    .notification-text {
        font-size: 1rem;
        max-width: 90vw;
    }
    .acc_id_sec {
        padding: 20px 6vw 14px 6vw;
        min-width: 0;
        max-width: 98vw;
        width: 98vw;
        box-sizing: border-box;
        border-radius: 22px;
    }
    .acc_id_sec p {
        font-size: 1rem;
    }
}
</style>';

echo '<div class="account-container">';
echo '    <div class="topmoviestext2">';
echo '        <div class="item toptext11">';
echo '            <h4 class="notification-heading">CONTA</h4>';
echo '            <p class="notification-text">Dê uma olhada nos detalhes da sua conta, incluindo todos os detalhes da sua assinatura, como expiração da conta.</p>';
echo '        </div>';
echo '        <div class="item">';
echo '            <img src="assets/images/adminimg.png" alt="Avatar">';
echo '        </div>';
echo '    </div>';
echo '    <div class="acc_id_sec">';
echo '        <div class="item">';
echo '            <p><strong>ID DA CONTA:</strong> ' . htmlspecialchars($acc_user) . '</p>';
echo '        </div>';
echo '        <div class="item">';
echo '            <p><strong>Data de validade:</strong> ' . htmlspecialchars($expire) . '</p>';
echo '        </div>';
echo '        <div class="item">';
echo '            <p><strong>Status da conta:</strong> ';
if (strtolower($acc_active) == "active") {
    echo '<span class="status">Ativa</span>';
} else {
    echo '<span class="status inactive">Inativa</span>';
}
echo '            </p>';
echo '        </div>';
echo '    </div>';
echo '</div>';

echo '<script>
$(function() {
  $("#myaccount_nav").addClass("active_menu");
});
</script>';
?>
