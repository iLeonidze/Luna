<?=$page->getHTMLBaseHead()?>
    </head>
    <body>
        <h1><?=$page->getContent("common","name")?></h1>
        <p>Price: <?=$page->getContent("common","price")?></p>
        <p>Code: <?=$page->getContent("common","code")?></p>
        <p>Description: <?=$page->getContent("common","description")?></p>
    </body>
</html>