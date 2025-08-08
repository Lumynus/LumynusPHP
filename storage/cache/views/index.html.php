<html>

<head>

</head>

<body>

<div id="app">


<input type="text" value="Teste">


<input type="date" value="Teste">


</div>


<div>
<button data-luma-model="teste">Oi</button>
</div>

<?= htmlspecialchars($conteudo) ?>



<div data-luma-if="test">
Avava
</div> 
    <script src="./resources/js/oi.js" type="module" integrity='sha384-OLBgp1GsljhM2TJ+sbHjaiH9txEUvgdDTAzHv2P24donTt6/529l+9Ua0vFImLlb' crossorigin="anonymous"></script>

<?php echo \Lumynus\Bundle\Framework\Luma::render('errors/404.php', ['conteudo' => $conteudo]); ?>  

<input type="hidden" name="<?= $nameCSRF ?>" value="<?= $tokenSecurityCSRF ?>">
</body>

</html> 