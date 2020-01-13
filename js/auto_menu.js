$( document ).ready(function() {

    var indexHtml = '';
    indexHtml = indexHtml + '<ul class="nav">';
    $( "h1" ).each(function() {
        if(typeof $( this ).attr('id') != 'undefined'){

            indexHtml = indexHtml + '<li><a href="#' + $( this ).attr("id") + '"><b>' + $( this ).html() + '</b></a>';

            indexHtml = indexHtml + '<ul class="nav h2item">';
            $("h2[id^='" + $( this ).attr("id") + "_']").each(function() {
                if(typeof $( this ).attr('id') != 'undefined'){
                    indexHtml = indexHtml + '<li><a href="#' + $( this ).attr('id') + '">' + $( this ).html() + '</a>';

                    indexHtml = indexHtml + '<ul class="nav h3item">';
                    $("h3[id^='" + $( this ).attr("id") + "_']").each(function() {
                        if(typeof $( this ).attr('id') != 'undefined'){
                            indexHtml = indexHtml + '<li><a href="#' + $( this ).attr('id') + '">' + $( this ).html() + '</a></li>';
                        }
                    });
                    indexHtml = indexHtml + '</li></ul>';

                }
            });
            indexHtml = indexHtml + '</li></ul>';

        }
    });
    indexHtml = indexHtml + '</ul>';
    $('.menu-items').html(indexHtml);


    $('body')
        .scrollspy({target: '.menu-items'})
        .on('activate.bs.scrollspy', function () {
            $('.h2item').hide();
            $('.h3item').hide();

            var h2active = $('.h2item > .active');
            h2active.parent().show();
            h2active.find('ul').show();
            $('.active > .h2item').show(300);
        });

    /*$('.menu li').click(function () {
        //$('.hideH2').hide();
        $(this).parent().find('ul').show();
    });*/

});