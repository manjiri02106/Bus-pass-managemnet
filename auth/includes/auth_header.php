<?php
/**
 * includes/auth_header.php — Shared <head> + page shell for auth screens.
 * Usage: $pageTitle = 'Login'; require 'includes/auth_header.php';
 */
if (session_status() === PHP_SESSION_NONE) { require_once __DIR__ . '/../config/session.php'; }
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> · <?= e(APP_NAME) ?></title>
  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      darkMode: 'class',
      theme: {
        extend: {
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
          },
        },
      },
    }
  </script>
  <!-- Bootstrap Icons (for icon compatibility) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(BASE_URL) ?>/assets/css/auth.css">
</head>
<body class="h-full bg-white dark:bg-[#050505] text-black dark:text-white antialiased [font-synthesis:none]">

  <div class="grid min-h-screen lg:grid-cols-[0.94fr_1.06fr]">
    
    <!-- LEFT PANEL: ShaderGradient-style animated mesh -->
    <div class="relative flex min-h-[360px] lg:min-h-0 overflow-hidden grain-bg p-8 sm:p-12 lg:p-16 text-white order-last lg:order-first">
      <!-- Animated colour orbs (animated by CSS keyframes) -->
      <div class="gb-orb gb-orb-1" aria-hidden="true"></div>
      <div class="gb-orb gb-orb-2" aria-hidden="true"></div>
      <div class="gb-orb gb-orb-3" aria-hidden="true"></div>
      <div class="gb-orb gb-orb-4" aria-hidden="true"></div>

      <div class="relative z-10 flex h-full w-full flex-col justify-between">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 backdrop-blur-md text-xs font-semibold tracking-wider uppercase text-orange-200 border border-white/10 mb-8 sm:mb-12">
            <i class="bi bi-bus-front-fill"></i> Bus Pass System
          </div>
          <h2 class="max-w-[620px] text-4xl sm:text-5xl lg:text-[64px] font-medium tracking-[-0.05em] leading-[0.98] text-white">
            Think fast,
            <br />
            Travel faster
          </h2>
          <p class="mt-4 text-white/70 max-w-md text-sm sm:text-base leading-relaxed">
            Quick approvals, dynamic routes, and instant digital pass generations.
          </p>
        </div>

        <div class="mt-12 lg:mt-auto">
          <a
            href="#"
            class="inline-flex h-12 items-center gap-3 rounded-[10px] border border-white/25 px-5 text-sm font-medium text-white/85 backdrop-blur-sm transition-colors hover:border-white/45 hover:text-white"
          >
            <i class="bi bi-info-circle text-lg"></i>
            <span class="whitespace-nowrap">Need assistance? Support Center</span>
          </a>
        </div>
      </div>
    </div>
    
    <!-- RIGHT PANEL: Forms Container -->
    <div class="flex min-h-[600px] lg:min-h-0 items-center justify-center bg-white dark:bg-[#0a0a0a] px-6 py-12 sm:px-10 lg:px-14 xl:px-20 order-first lg:order-last">
      <div class="w-full max-w-[480px]">
        <div>
          <h1 class="text-3xl sm:text-4xl font-medium tracking-[-0.04em] text-black dark:text-white">
            <?= e($pageTitle) ?>
          </h1>
          <p class="mt-2 text-base text-black/60 dark:text-white/55">
            <?= e(APP_TAGLINE) ?>
          </p>
        </div>

        <div class="mt-8">
          <?= render_flash() ?>
        </div>

