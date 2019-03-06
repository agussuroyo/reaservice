jQuery(function ($) {
    $(function () {
        var rea_scrape = {

            init: function () {

                this.hit();
            },

            hit: function () {

                var times = 0;

                $(document).on('scrapeComplete', function (e, r) {

                    if (times > 0) {
                        clearTimeout(times);
                    }

                    times = setTimeout(function () {
                        if (r.do == 1) {
                            rea_scrape.fetch({
                                page: r.next_page
                            });
                        }
                    }, 10);
                });

                $(document).on('click', 'button[name="scrape"]', function (e) {
                    e.preventDefault();
                    rea_scrape.fetch();
                });

            },

            fetch: function (args) {
                var options = $.extend({
                    page: $('input[name="page"]').val(),
                    keywords: $('input[name="keywords"]').val(),
                    channel: $('select[name="channel"]').val()
                }, args);

                var req = $.ajax({
                    url: scrape_app.baseurl + 'index.php/rea/hit/',
                    data: {
                        page: options.page,
                        keywords: options.keywords,
                        channel: options.channel
                    }
                });

                req.then(function (r) {
                    $(document).trigger('scrapeComplete', [r]);
                });
            }
        };

        rea_scrape.init();
    });
});