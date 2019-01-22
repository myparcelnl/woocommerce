$( document ).ready(function() {
    $('#NL').click();
});

/*
* Load the correct language
*/
$(document).on("click",".languagepicker > li", function () {
    var clickedLanguage = $(this).attr('id'); /* Get the id of the selected language */
    var lowerCase = clickedLanguage.toLowerCase();

    $(this).prependTo('ul.languagepicker'); /* set the selected language at first option */
    $(".all-content").load("language/"+ lowerCase +"_"+ clickedLanguage +".html");
});