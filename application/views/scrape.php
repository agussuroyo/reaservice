<html>
    <head>
        <title></title>       
    </head>
    <body>


        <form accept-charset="" action="" method="get">
            <select name="channel">
                <option value="buy" <?php echo $channel == 'buy' ? 'selected=""' : ''; ?>>Buy</option>
                <option value="sold" <?php echo $channel == 'sold' ? 'selected=""' : ''; ?>>Sold</option>
            </select>
            <br/>
            <input type="number" value="<?php echo $page; ?>" name="page" />
            <br/>
            <input type="text" value="<?php echo $keywords; ?>" name="keywords" />
            <br/>
            <button type="button" name="scrape" value="1">Scrape</button>
        </form>

        <script type="text/javascript">
            var scrape_app = {
                siteurl: '<?php echo $siteurl; ?>',
                baseurl: '<?php echo $baseurl; ?>',
            };
        </script>
        <script type="text/javascript" src="<?php echo $baseurl; ?>/assets/build/vendor/jquery/jquery-3.3.1.min.js"></script>
        <script type="text/javascript" src="<?php echo $baseurl; ?>/assets/src/js/scrape.js"></script>
    </body>
</html>
