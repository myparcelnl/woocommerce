###
# Constants
###
DISABLED = 'disabled'

HVO_DEFAULT_TEXT = 'Handtekening voor ontvangst'
AO_DEFAULT_TEXT = 'Alleen geadresseerde'

NATIONAL = 'NL'
CARRIER = 1

MORNING_DELIVERY = 'morning'
DEFAULT_DELIVERY = 'default'
EVENING_DELIVERY = 'night'
PICKUP = 'pickup'
PICKUP_EXPRESS = 'pickup_express'

POST_NL_TRANSLATION =
  morning: 'morning'
  standard: 'default'
  night: 'night'

DAYS_OF_THE_WEEK = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
DAYS_OF_THE_WEEK_TRANSLATED = ['ma', 'di', 'wo', 'do', 'vr', 'za', 'zo']

MORNING_PICKUP = '08:30:00'
NORMAL_PICKUP = '16:00:00'

PICKUP_TIMES =
  "#{MORNING_PICKUP}": 'morning'
  "#{NORMAL_PICKUP}": 'normal'
  
@MyParcel = class Application

  ###
  # Setup initial variables
  ###
  constructor: (options) ->

    # Sets moment timeformatting to dutch
    moment.locale NATIONAL

    # Construct window.mypa if not already set
    window.mypa ?= settings:{}
    window.mypa.settings.base_url ?= "//localhost:8080/api/delivery_options"

    @el = document.getElementById('myparcel')
    @$el = jquery('myparcel')
    @shadow = @el.createShadowRoot() unless @shadow?

    @render()
    @expose(@updatePage, 'updatePage')
    @expose(this, 'activeInstance')

  ###
  # Reloads the HTML form the template.
  ###
  render: ->
    @shadow.innerHTML = document.getElementById('myparcel-template').innerHTML
    try
      WebComponents.ShadowCSS?.shimStyling( shadow, 'myparcel' )
    catch
      console.log 'Cannot shim CSS'

    #Bind on rendering inputfield
    @bindInputListeners()

  ###
  # Puts function in window.mypa effectively exposing the function.
  ###
  expose: (fn, name)->
    window.mypa.fn ?= {}
    window.mypa.fn[name] = fn

  ###
  # Adds the listeners for the inputfields.
  ###
  bindInputListeners: ->
    jquery('#mypa-signed').on 'change', (e)=>
      $('#mypa-signed').prop 'checked', jquery('#mypa-signed').prop 'checked'

    jquery('#mypa-recipient-only').on 'change', (e)=>
      $('#mypa-only-recipient').prop 'checked', jquery('#mypa-recipient-only').prop 'checked'

    jquery('#mypa-input').on 'change', (e)=>
      json = jquery('#mypa-input').val()
      if json is ''
        $('input[name=mypa-delivery-time]:checked').prop 'checked', false
        $('input[name=mypa-delivery-type]:checked').prop 'checked', false
        return
      for el in $('input[name=mypa-delivery-time]')
        if $(el).val() is json
          $(el).prop('checked', true)
          return

  ###
  # Fetches devliery options and an overall page update.
  ###
  updatePage: (postal_code, number, street)->
    for key, item of window.mypa.settings.price
      throw new Error 'Price needs to be of type string' unless typeof(item) is 'string' or typeof(item) is 'function'
    settings = window.mypa.settings
    urlBase = settings.base_url
    number ?= settings.number
    postal_code ?= settings.postal_code
    street ?= settings.street

    unless street? or postal_code? or number?
      $('#mypa-no-options').html 'Geen adres opgegeven'
      $('.mypa-overlay').removeClass 'mypa-hidden'
      return

    $('#mypa-no-options').html 'Bezig met laden...'
    $('.mypa-overlay').removeClass 'mypa-hidden'
    $('.mypa-location').html("#{street} #{number}")
    options =
      url: urlBase
      data:
        cc: NATIONAL
        carrier: CARRIER
        number: number
        postal_code: postal_code
        delivery_time: settings.delivery_time if settings.delivery_time?
        delivery_date: settings.delivery_date if settings.delivery_date?
        cutoff_time: settings.cutoff_time if settings.cutoff_time?
        dropoff_days: settings.dropoff_days if settings.dropoff_days?
        dropoff_delay: settings.dropoff_delay if settings.dropoff_delay?
        deliverydays_window: settings.deliverydays_window if settings.deliverydays_window?
        exlude_delivery_type: settings.exclude_delivery_type if settings.exclude_delivery_type?
      success: renderPage

    jquery.ajax(options)

class Slider

  ###
  # Renders the available days for delivery
  ###
  constructor: (deliveryDays) ->

    @deliveryDays = deliveryDays
    if deliveryDays.length < 1
      $('mypa-delivery-row').addClass 'mypa-hidden'
      return
    $('mypa-delivery-row').removeClass 'mypa-hidden'

    deliveryDays.sort @orderDays

    deliveryTimes = window.mypa.sortedDeliverytimes = {}
    $el = $('#mypa-tabs').html ''
    window.mypa.deliveryDays = deliveryDays.length

    index = 0
    for delivery in @deliveryDays

      deliveryTimes[delivery.date] = delivery.time

      date = moment(delivery.date)
      html = """
        <input type="radio" id="mypa-date-#{index}" class="mypa-date" name="date" checked value="#{delivery.date}">
        <label for='mypa-date-#{index}' class='mypa-tab active'>
          <span class='day-of-the-week'>#{date.format 'dddd'}</span>
          <br>
          <span class='date'>#{date.format 'DD MMMM'}</span>
        </label>
      """
      $el.append html
      index++

    $tabs = $('.mypa-tab')
    if $tabs.length > 0
      $tabs.bind 'click', updateDelivery
      $tabs[0].click()

    $("#mypa-tabs").attr 'style', "width:#{@deliveryDays.length * 105}px"
    @makeSlider()

  ###
  # Initializes the slider
  ###
  makeSlider: ->
    @slider = {}
    @slider.currentBar = 0
    @slider.bars = window.mypa.deliveryDays * 105 / $('#mypa-tabs-container')[0].offsetWidth

    $('mypa-tabs').attr 'style', "width:#{window.mypa.deliveryDays * 105}px;"

    $('#mypa-date-slider-right').removeClass 'mypa-slider-disabled'
    $('#mypa-date-slider-left').unbind().bind 'click', @slideLeft
    $('#mypa-date-slider-right').unbind().bind 'click', @slideRight

  ###
  # Event handler for sliding the date slider to the left
  ###
  slideLeft: (e)=>
    slider = @slider
    if slider.currentBar is 1
      $(e.currentTarget).addClass 'mypa-slider-disabled'
    else if slider.currentBar < 1
      return false
    else
      $(e.currentTarget).removeClass 'mypa-slider-disabled'

    $('#mypa-date-slider-right').removeClass 'mypa-slider-disabled'
    slider.currentBar--
    $el = $ '#mypa-tabs'
    left = slider.currentBar * 100 * -1
    $el.attr 'style', "left:#{left}%; width:#{window.mypa.deliveryDays * 105}px"

  ###
  # Event handler for sliding the date slider to the right
  ###
  slideRight: (e)=>
    slider = @slider
    if parseInt(slider.currentBar) is parseInt slider.bars - 1
      $(e.currentTarget).addClass 'mypa-slider-disabled'
    else if slider.currentBar >= slider.bars - 1
      return false
    else
      $(e.currentTarget).removeClass 'mypa-slider-disabled'

    $('#mypa-date-slider-left').removeClass 'mypa-slider-disabled'
    slider.currentBar++
    $el = $ '#mypa-tabs'
    left = slider.currentBar * 100 * -1
    $el.attr 'style', "left:#{left}%; width:#{window.mypa.deliveryDays * 105}px"

  ###
  # Order function for the delivery array
  ###
  orderDays: (dayA, dayB) ->
    dateA = moment dayA.date
    dateB = moment dayB.date

    max = moment.max dateA, dateB

    return 1 if max is dateA

    return -1


jquery = mypajQuery if mypajQuery?
jquery ?= $
jquery ?= jQuery
$ = (selector) ->
  return jquery(document.getElementById('myparcel').shadowRoot).find selector

displayOtherTab = ->
  $ '.mypa-tab-container'
    .toggleClass 'mypa-slider-pos-1'
    .toggleClass 'mypa-slider-pos-0'

###
# Starts the render of the delivery options with the preset config
###
renderPage = (response)->
  if response.data.message is 'No results'
    $('#mypa-no-options').html 'Geen bezorgopties gevonden voor het opgegeven adres.'
    $('.mypa-overlay').removeClass 'mypa-hidden'
    return
  $('.mypa-overlay').addClass 'mypa-hidden'
  $('#mypa-delivery-option-check').bind 'click', -> renderDeliveryOptions $('input[name=date]:checked').val()
  new Slider response.data.delivery
  preparePickup response.data.pickup

  $('#mypa-delivery-options-title').on 'click', ->
    date = $('input[name=date]:checked').val()
    renderDeliveryOptions date
    updateInputField()

  $('#mypa-pickup-options-title').on 'click', ->
    $('#mypa-pickup').prop 'checked', true
    updateInputField()
  updateInputField()

preparePickup = (pickupOptions) ->

  if pickupOptions.length < 1
    $('#mypa-pickup-row').addClass('mypa-hidden')
    return
  $('#mypa-pickup-row').removeClass('mypa-hidden')
  pickupPrice = window.mypa.settings.price[PICKUP]
  pickupExpressPrice = window.mypa.settings.price[PICKUP_EXPRESS]

  $('.mypa-pickup-price').html pickupPrice
  $('.mypa-pickup-price').toggleClass 'mypa-hidden', (not pickupPrice?)

  $('.mypa-pickup-express-price').html pickupExpressPrice
  $('.mypa-pickup-express-price').toggleClass 'mypa-hidden', (not pickupExpressPrice?)

  window.mypa.pickupFiltered = filter = {}

  pickupOptions = sortLocationsOnDistance pickupOptions
  for pickupLocation in pickupOptions
    for time in pickupLocation.time
      filter[PICKUP_TIMES[time.start]] ?= []
      filter[PICKUP_TIMES[time.start]].push pickupLocation

  if not filter[PICKUP_TIMES[MORNING_PICKUP]]?
    $('#mypa-pickup-express').parent().css display: 'none'

  showDefaultPickupLocation '#mypa-pickup-address', filter[PICKUP_TIMES[NORMAL_PICKUP]][0]
  showDefaultPickupLocation '#mypa-pickup-express-address', filter[PICKUP_TIMES[MORNING_PICKUP]][0]

  $('#mypa-pickup-address').off().bind 'click', renderPickup
  $('#mypa-pickup-express-address').off().bind 'click', renderExpressPickup

  $('.mypa-pickup-selector').on 'click', updateInputField

###
# Sorts the pickup options on nearest location
###
sortLocationsOnDistance = (pickupOptions)->
  pickupOptions.sort (a,b)->
    return parseInt(a.distance) - parseInt(b.distance)

###
# Displays the default location behind the pickup location
###
showDefaultPickupLocation = (selector, item)->
  html = " - #{item.location}, #{item.street} #{item.number}"
  $(selector).html html
  $(selector).parent().find('input').val JSON.stringify item
  updateInputField()

###
# Set the pickup time HTML and start rendering the locations page
###
renderPickup = ->
  renderPickupLocation window.mypa.pickupFiltered[PICKUP_TIMES[NORMAL_PICKUP]]
  $('.mypa-location-time').html('- Vanaf 16.00 uur')
  $('#mypa-pickup').prop 'checked', true
  return false

###
# Set the pickup time HTML and start rendering the locations page
###
renderExpressPickup = ->
  renderPickupLocation window.mypa.pickupFiltered[PICKUP_TIMES[MORNING_PICKUP]]
  $('.mypa-location-time').html('- Vanaf 08.30 uur')
  $('#mypa-pickup-express').prop 'checked', true
  return false

###
# Renders the locations in the array order given in data
###
renderPickupLocation = (data)->
  displayOtherTab()
  $('.mypa-onoffswitch-checkbox:checked').prop 'checked', false
  checkCombination()
  $('#mypa-location-container').html ''

  for index in [0..(data.length-1)]
    location = data[index]
    orderedHours = orderOpeningHours location.opening_hours

    openingHoursHtml = ''
    for day_index in [0..6] # number of days in the week.
      openingHoursHtml += """
        <div>
          <div class='mypa-day-of-the-week'>
            #{DAYS_OF_THE_WEEK_TRANSLATED[day_index]}:
          </div>
          <div class='mypa-opening-hours-list'>
      """
      for time in orderedHours[day_index]
        openingHoursHtml += "<div>#{time}</div>"
      if orderedHours[day_index].length < 1
        openingHoursHtml += "<div><i>Gesloten</i></div>"

      openingHoursHtml += '</div></div>'

    html = """
      <div for='mypa-pickup-location-#{index}' class="mypa-row-lg afhalen-row">
        <div class="afhalen-right">
          <i class='mypa-info'>
          </i>
        </div>
        <div class='mypa-opening-hours'>
          #{openingHoursHtml}
        </div>
        <label for='mypa-pickup-location-#{index}' class="afhalen-left">
          <div class="afhalen-check">
            <input id="mypa-pickup-location-#{index}" type="radio" name="mypa-pickup-option" value='#{JSON.stringify location}'>
            <label for='mypa-pickup-location-#{index}' class='mypa-row-title'>
              <div class="mypa-checkmark mypa-main">
                <div class="mypa-circle"></div>
                <div class="mypa-checkmark-stem"></div>
                <div class="mypa-checkmark-kick"></div>
              </div>
            </label>
          </div>
          <div class='afhalen-tekst'>
            <span class="mypa-highlight mypa-inline-block">#{location.location}, <b class='mypa-inline-block'>#{location.street} #{location.number}</b>,
            <i class='mypa-inline-block'>#{String(Math.round(location.distance/100)/10).replace '.', ','} Km</i></span>
          </div>
        </label>
      </div>
    """
    $('#mypa-location-container').append html
  $('input[name=mypa-pickup-option]').bind 'click', (e)->
    displayOtherTab()
    obj = JSON.parse $(e.currentTarget).val()
    selector = '#' + $('input[name=mypa-delivery-time]:checked').parent().find('span.mypa-address').attr('id')
    showDefaultPickupLocation selector, obj

orderOpeningHours = (opening_hours)->
  array = []
  for day in DAYS_OF_THE_WEEK
    array.push opening_hours[day]

  return array

updateDelivery = (e)->
  return unless $('#mypa-delivery-option-check').prop('checked') is true
  date = $("##{$(e.currentTarget).prop 'for'}")[0].value
  renderDeliveryOptions date
  updateInputField()

renderDeliveryOptions  = (date)->
  $('#mypa-delivery-options').html ''

  html = ''
  deliveryTimes = window.mypa.sortedDeliverytimes[date]
  index = 0
  for time in deliveryTimes


    time.price_comment = EVENING_DELIVERY if time.price_comment is 'avond'
    price = window.mypa.settings.price[POST_NL_TRANSLATION[time.price_comment]]
    json =
      date: date
      time: [time]


    checked = ''
    checked = "checked" if time.price_comment is 'standard'
    html += """
      <label for="mypa-time-#{index}" class='mypa-row-subitem'>
        <input id='mypa-time-#{index}' type="radio" name="mypa-delivery-time" value='#{JSON.stringify json}' #{checked}>
        <label for="mypa-time-#{index}" class="mypa-checkmark">
          <div class="mypa-circle mypa-circle-checked"></div>
          <div class="mypa-checkmark-stem"></div>
          <div class="mypa-checkmark-kick"></div>
        </label>
        <span class="mypa-highlight">#{moment(time.start, 'HH:mm:SS').format 'H.mm'} - #{moment(time.end, 'HH:mm:SS').format 'H.mm'} uur</span>
    """
    html += "<span class='mypa-price'>#{price}</span>" if price?
    html += "</label>"

    index++

  hvoPrice = window.mypa.settings.price.signed
  hvoText = window.mypa.settings.text?.signed
  hvoText ?= HVO_DEFAULT_TEXT
  onlyRecipientPrice = window.mypa.settings.price.only_recipient
  onlyRecipientText = window.mypa.settings.text?.only_recipient
  onlyRecipientText ?= AO_DEFAULT_TEXT
  combinatedPrice = window.mypa.settings.price.combi_options

  combine = onlyRecipientPrice isnt 'disabled' and hvoPrice isnt 'disabled' and combinatedPrice?

  if combine
    html += "<div class='mypa-combination-price'><span class='mypa-price mypa-hidden'>#{combinatedPrice}</span>"

  if onlyRecipientPrice isnt DISABLED
    html += """
      <label for="mypa-only-recipient" class='mypa-row-subitem'>
        <input type="checkbox" name="mypa-only-recipient" class="mypa-onoffswitch-checkbox" id="mypa-only-recipient">
        <div class="mypa-switch-container">
          <div class="mypa-onoffswitch">
            <label class="mypa-onoffswitch-label" for="mypa-only-recipient">
              <span class="mypa-onoffswitch-inner"></span>
              <span class="mypa-onoffswitch-switch"></span>
            </label>
          </div>
        </div>
        <span>#{onlyRecipientText}
    """
    html += "<span class='mypa-price'>#{onlyRecipientPrice}</span>" if onlyRecipientPrice?
    html += "</span></label>"

  if hvoPrice isnt DISABLED
    html += """
      <label for="mypa-signed" class='mypa-row-subitem'>
        <input type="checkbox" name="mypa-signed" class="mypa-onoffswitch-checkbox" id="mypa-signed">
        <div class="mypa-switch-container">
          <div class="mypa-onoffswitch">
            <label class="mypa-onoffswitch-label" for="mypa-signed">
              <span class="mypa-onoffswitch-inner"></span>
            <span class="mypa-onoffswitch-switch"></span>
            </label>
          </div>
        </div>
        <span>#{hvoText}
    """
    html += "<span class='mypa-price'>#{hvoPrice}</span>" if hvoPrice
    html += "</span></label>"

  if combine
    html += "</div>"

  $('#mypa-delivery-options').html html

  $('.mypa-combination-price label').on 'click', checkCombination
  $('#mypa-delivery-options label.mypa-row-subitem input[name=mypa-delivery-time]').on 'change', (e)->
    deliveryType = JSON.parse($(e.currentTarget).val())['time'][0]['price_comment']
    if deliveryType in [MORNING_DELIVERY, EVENING_DELIVERY]
      $('input#mypa-only-recipient').prop 'checked', true
        .prop 'disabled', true
      $('label[for=mypa-only-recipient] span.mypa-price').html 'incl.'
    else
      onlyRecipientPrice = window.mypa.settings.price.only_recipient
      $('input#mypa-only-recipient').prop 'disabled', false
      $('label[for=mypa-only-recipient] span.mypa-price').html onlyRecipientPrice
    checkCombination()

  if $('input[name=mypa-delivery-time]:checked').length < 1
    $($('input[name=mypa-delivery-time]')[0]).prop 'checked', true

  $('div#mypa-delivery-row label').bind 'click', updateInputField

###
# Checks if the combination of options applies and displays this if needed.
###
checkCombination = ->
  json = $('#mypa-delivery-options .mypa-row-subitem input[name=mypa-delivery-time]:checked').val()
  deliveryType = JSON.parse(json)['time'][0]['price_comment'] if json?
  inclusiveOption = deliveryType in [MORNING_DELIVERY, EVENING_DELIVERY]
  combination = $('input[name=mypa-only-recipient]').prop('checked') and $('input[name=mypa-signed]').prop('checked') and not inclusiveOption
  $('.mypa-combination-price').toggleClass 'mypa-combination-price-active', combination
  $('.mypa-combination-price > .mypa-price').toggleClass 'mypa-price-active', combination
  $('.mypa-combination-price > .mypa-price').toggleClass 'mypa-hidden', not combination
  $('.mypa-combination-price label .mypa-price').toggleClass 'mypa-hidden', combination


###
# Sets the json to the selected input field to be with the form
###
updateInputField = ->
  json = $('input[name=mypa-delivery-time]:checked').val()

  if jquery('#mypa-input').val() isnt json
    jquery('#mypa-input').val json
    jquery('#mypa-input').trigger 'change'

  if jquery('#mypa-signed').val() isnt $('#mypa-signed').prop 'checked'
    jquery('#mypa-signed').prop 'checked', $('#mypa-signed').prop 'checked'
    jquery('#mypa-signed').trigger 'change'

  if jquery('#mypa-recipient-only').val() isnt $('#mypa-recipient-only').prop 'checked'
    jquery('#mypa-recipient-only').prop 'checked', $('#mypa-only-recipient').prop 'checked'
    jquery('#mypa-recipient-only').trigger 'change'
