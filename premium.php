<?php
require_once __DIR__ . '/includes/planos_helpers.php';
$pwb_config = pwb_load_config();
$nome_painel = $pwb_config['nome_painel'];
$logo_painel = $pwb_config['logo'];
$whatsapp_numero = $pwb_config['whatsapp_numero'];
$planos = $pwb_config['planos'];
$mensagem_padrao_assinar = "Olá! Quero assinar o {$nome_painel}.";

// Se o cliente já estiver logado no web player, inclui o usuário dele na
// mensagem do WhatsApp (sem exigir login pra acessar esta página).
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pwb_usuario = $_SESSION['username'] ?? '';
if ($pwb_usuario !== '') {
    $mensagem_padrao_assinar .= " Meu usuário é: {$pwb_usuario}.";
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Assine o <?php echo htmlspecialchars($nome_painel); ?> escolhendo o plano ideal." />
    <meta name="theme-color" content="#111827" />
    <?php if (!empty($logo_painel)): ?>
        <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($logo_painel); ?>">
    <?php endif; ?>

    <title>Assine já - <?php echo htmlspecialchars($nome_painel); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">
    <link href="https://unpkg.com/tailwindcss@^2/dist/tailwind.min.css" rel="stylesheet">

    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #1d4ed8;
            --accent: #7c3aed;
        }

        body {
            font-family: 'Poppins', sans-serif;
            scroll-behavior: smooth;
            overflow-x: hidden;
            background-color: #0f172a;
            color: white;
        }

        .streaming-gradient {
            background: linear-gradient(135deg, #111827 0%, #1f2937 50%, #374151 100%);
        }

        .dark-glass {
            background: rgba(17, 24, 39, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .plan-card {
            transition: all 0.4s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .plan-card:hover {
            transform: scale(1.03);
            border-color: var(--primary);
        }

        .plan-popular {
            transform: scale(1.05);
            border: 2px solid var(--primary);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in {
            animation: fadeIn 0.8s ease forwards;
        }

        .hero-icon {
            font-size: 3.2rem;
            background: linear-gradient(135deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>

<body>

    <!-- Navegação -->
    <nav class="dark-glass py-4 sticky top-0 z-40">
        <div class="container mx-auto px-6">
            <div class="flex items-center justify-between">
                <a href="homex.php" class="flex items-center gap-3">
                    <?php if (!empty($logo_painel)): ?>
                        <img src="<?php echo htmlspecialchars($logo_painel); ?>" alt="<?php echo htmlspecialchars($nome_painel); ?>" class="h-10 w-auto">
                    <?php endif; ?>
                    <span class="text-2xl font-bold bg-clip-text text-transparent bg-gradient-to-r from-blue-400 to-purple-500">
                        <?php echo htmlspecialchars($nome_painel); ?>
                    </span>
                </a>
                <div class="flex items-center gap-3">
                    <a href="renovar.php"
                        class="hidden sm:inline-block border border-white/30 text-white px-5 py-2 rounded-full hover:bg-white/10 transition duration-300">
                        Renovar assinatura
                    </a>
                    <a href="homex.php"
                        class="bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-2 rounded-full hover:shadow-lg transform hover:scale-105 transition duration-300">
                        Voltar ao início
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="streaming-gradient py-16">
        <div class="container mx-auto px-6 text-center animate-fade-in">
            <div class="hero-icon mb-4"><i class="fas fa-bolt"></i></div>
            <h1 class="text-3xl md:text-5xl font-bold mb-4">Assine já</h1>
            <p class="text-lg text-gray-300 max-w-2xl mx-auto">
                Escolha abaixo o plano ideal do <?php echo htmlspecialchars($nome_painel); ?> e finalize sua assinatura
                diretamente pelo WhatsApp.
            </p>
        </div>
    </section>

    <!-- Planos -->
    <section id="planos" class="py-20">
        <div class="container mx-auto px-6">
            <?php $pwb_modo = 'assinar'; include __DIR__ . '/includes/planos_grid.php'; ?>

            <div class="mt-16 text-center" data-aos="fade-up">
                <p class="text-lg text-gray-300 mb-6">
                    Não encontrou seu plano ou tem alguma dúvida? Fale direto com a gente.
                </p>
                <a href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/\D+/', '', $whatsapp_numero)); ?>?text=<?php echo rawurlencode($mensagem_padrao_assinar); ?>"
                    target="_blank"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-green-500 to-emerald-700 text-white px-8 py-4 rounded-xl hover:shadow-lg transform hover:scale-105 transition duration-300">
                    <i class="fab fa-whatsapp text-xl"></i>
                    <span>Falar no WhatsApp</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Botão de contato flutuante móvel -->
    <div class="fixed bottom-4 right-4 md:hidden z-50">
        <a href="https://wa.me/<?php echo htmlspecialchars(preg_replace('/\D+/', '', $whatsapp_numero)); ?>?text=<?php echo rawurlencode($mensagem_padrao_assinar); ?>"
            target="_blank"
            class="bg-gradient-to-r from-green-500 to-emerald-600 text-white w-14 h-14 rounded-full shadow-lg flex items-center justify-center">
            <i class="fab fa-whatsapp text-2xl"></i>
        </a>
    </div>

    <footer class="bg-gray-900 text-white py-10">
        <div class="container mx-auto px-6 text-center">
            <p class="text-gray-500 text-sm">
                &copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($nome_painel); ?>. Todos os direitos reservados.
            </p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            AOS.init({ duration: 800, once: true, offset: 100 });
        });
    </script>
</body>

</html>
