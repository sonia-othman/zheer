<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" dir="<?php echo e(in_array(app()->getLocale(), ['ar', 'ku']) ? 'rtl' : 'ltr'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia><?php echo e(config('app.name', 'Laravel')); ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">

    <!-- Scripts -->
    <?php echo app('Tighten\Ziggy\BladeRouteGenerator')->generate(); ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/js/app.js']); ?>
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->head; } ?>
    
    <!-- Manual RTL CSS (optional) -->
    <?php if(in_array(app()->getLocale(), ['ar', 'ku'])): ?>
        <?php echo app('Illuminate\Foundation\Vite')(['resources/css/rtl.css']); ?>
    <?php endif; ?>
    
    <script>
        window.Laravel = {
            locale: "<?php echo e(app()->getLocale()); ?>",
            fallbackLocale: "<?php echo e(config('app.fallback_locale')); ?>",
            isRtl: <?php echo e(in_array(app()->getLocale(), ['ar', 'ku']) ? 'true' : 'false'); ?>

        };
    </script>
</head>
<body class="font-sans antialiased">
    <?php if (!isset($__inertiaSsrDispatched)) { $__inertiaSsrDispatched = true; $__inertiaSsrResponse = app(\Inertia\Ssr\Gateway::class)->dispatch($page); }  if ($__inertiaSsrResponse) { echo $__inertiaSsrResponse->body; } else { ?><div id="app" data-page="<?php echo e(json_encode($page)); ?>"></div><?php } ?>
</body>
</html><?php /**PATH C:\Users\sonia\Desktop\iot-platform\resources\views/app.blade.php ENDPATH**/ ?>