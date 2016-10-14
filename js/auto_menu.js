$( document ).ready(function() {

    var indexHtml = '';
    indexHtml = indexHtml + '<ul>';
    $( "h1" ).each(function() {
        if(typeof $( this ).attr('id') != 'undefined'){

            indexHtml = indexHtml + '<li><a href="#' + $( this ).attr("id") + '"><b>' + $( this ).html() + '</b></a></li>';

            indexHtml = indexHtml + '<ul>';
            $("h2[id^='" + $( this ).attr("id") + "_']").each(function() {
                if(typeof $( this ).attr('id') != 'undefined'){
                    indexHtml = indexHtml + '<li><a href="#' + $( this ).attr('id') + '">' + $( this ).html() + '</a></li>';

                    indexHtml = indexHtml + '<ul>';
                    $("h3[id^='" + $( this ).attr("id") + "_']").each(function() {
                        if(typeof $( this ).attr('id') != 'undefined'){
                            indexHtml = indexHtml + '<li><a href="#' + $( this ).attr('id') + '">' + $( this ).html() + '</a></li>';
                        }
                    });
                    indexHtml = indexHtml + '</ul>';

                }
            });
            indexHtml = indexHtml + '</ul>';

        }
    });
    $('.menu').html(indexHtml);

    $(index).each(function() {
        console.log(this);
    });
    console.log(index);
    console.log('test');
});