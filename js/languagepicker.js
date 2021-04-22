var languages = {
    nl: '#NL',
    en: '#EN',
}

$(document).ready(function () {
    var selector = '#NL';


    var url = new URLSearchParams(window.location.search)
    var lang = url.get('lang');

    if (lang && Object.keys(languages).includes(lang)) {
        selector = languages[lang]
    }

    $(selector).click();
});

/*
* Load the correct language
*/
$(document).on("click", ".languagepicker > li", function () {
    var clickedLanguage = $(this).attr('id'); /* Get the id of the selected language */
    var lowerCase = clickedLanguage.toLowerCase();
    var params = new URLSearchParams(window.location.search);

    params.set('lang', lowerCase);

    var location = new URL(window.location.href);
    location.search = params.toString();
    window.history.replaceState( {} , window.title, location);

    $(this).prependTo('ul.languagepicker'); /* set the selected language at first option */
    $(".all-content").load("language/" + lowerCase + "_" + clickedLanguage + ".html");
});
