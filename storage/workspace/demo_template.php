<?=$page
->setStylesheet("assets/css/bootstrap.css")
->setStylesheet("assets/css/styles.css")
->setJSFile("assets/js/jquery.min.js",true)
->setJSFile("assets/js/bootstrap.js",false, true)
->setJSCode("console.log('Hello world!');")
->setJSCode("console.log('This is demo!');")
->setStyle("h1{font-family: \"Segoe UI\", Arial, sans-serif;font-size: 22px;}")
->getHTMLHead()
?>

    <body>
        Super site!
        <br>
        <ul>
<?foreach($page->getNavigation() as $element){?>
            <li>
                <?=(!is_null($element->getLink()) ? "<a href=\"".$element->getLink()."\">" : "")?><?=(!is_null($element->getName()) ? $element->getName() : "")?><?=(!is_null($element->getLink()) ? "</a>" : "")?>

                <ul>
<?foreach($element->getNavigation() as $subelement){?>
                        <li>
                            <?=(!is_null($subelement->getLink()) ? "<a href=\"".$subelement->getLink()."\">" : "")?><?=(!is_null($subelement->getName()) ? $subelement->getName() : "")?><?=(!is_null($subelement->getLink()) ? "</a>" : "")?>

                        </li>
<?}?>
                    </ul>
            </li>
<?}?>
        </ul>

        <h1><?=$page->getContent("common","name")?></h1>
        <p>Price: <?=$page->getContent("common","price")?></p>
        <p>Code: <?=$page->getContent("common","code")?></p>
        <p>Description: <?=$page->getContent("common","description")?></p>

        <div><?php
                $goodsPages = new Search();
                $foundGoodsPages = $goodsPages->byType("good")->from(0)->to(1)->find();
                foreach ($foundGoodsPages as $goodPage){
                    echo "\r\n\t\t\t#".$goodPage->getID()."<br>";
                }
            ?>
        </div>
    </body>
</html>