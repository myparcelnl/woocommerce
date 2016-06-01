$ = jQuery.noConflict()

window.mypa ?= {}
window.mypa.fn ?= {}
window.mypa.settings ?= {}
window.mypa.settings.price ?= {}
window.mypa.settings.base_url ?= "/api/delivery_options"

NATIONAL = 'NL'
CARRIER = 1

MORNING_DELIVERY = -> return 'morning'
DEFAULT_DELIVERY = 'default'
EVENING_DELIVERY = 'night'
PICKUP = 'pickup'
PICKUP_EXPRESS = 'pickup_express'

MORNING_PICKUP = '08:30:00'
NORMAL_PICKUP = '16:00:00'

PICKUP_TIMES =
  "#{MORNING_PICKUP}": 'morning'
  "#{NORMAL_PICKUP}": 'normal'

displayOtherTab = ->
  $ '.mypa-tab-container'
    .toggleClass 'mypa-slider-pos-1'
    .toggleClass 'mypa-slider-pos-0'

window.mypa.fn.updatePage = ->
  fetchDeliveryOptions()

fetchDeliveryOptions = (postal_code, number, street)->
  settings = window.mypa.settings
  urlBase = settings.base_url
  number ?= settings.number
  number ?= 100
  postal_code ?= settings.postal_code
  postal_code ?= '1442CE'
  street ?= settings.street
  street ?= 'Wormerplein'
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

  $.ajax(options)

###
# Starts the render of the delivery options with the preset config
###
renderPage = (response)->
  renderDays response.data.delivery
  preparePickup response.data.pickup

preparePickup = (pickupOptions) ->

  pickupPrice = window.mypa.settings.price[PICKUP]
  pickupPrice ?= 'GRATIS'
  pickupExpressPrice = window.mypa.settings.price[PICKUP_EXPRESS]
  pickupExpressPrice ?= 'GRATIS'

  $('.mypa-pickup-price').html pickupPrice
  $('.mypa-pickup-express-price').html pickupExpressPrice

  window.mypa.pickupFiltered = filter = {}

  for pickupLocation in pickupOptions
    for time in pickupLocation.time
      filter[PICKUP_TIMES[time.start]] ?= []
      filter[PICKUP_TIMES[time.start]].push pickupLocation

  if not filter[PICKUP_TIMES[MORNING_PICKUP]]?
    $('#mypa-pickup-express').parent().css display: 'none'

  $('label[for=mypa-pickup]').off().bind 'click', renderPickup
  $('label[for=mypa-pickup-express]').off().bind 'click', renderExpressPickup

renderPickup = ->
  renderPickupLocation window.mypa.pickupFiltered[PICKUP_TIMES[NORMAL_PICKUP]]
  $('.mypa-location-time').html('- Vanaf 16.00 uur')
  $('#mypa-pickup').prop 'checked', true
  return false

renderExpressPickup = ->
  renderPickupLocation window.mypa.pickupFiltered[PICKUP_TIMES[MORNING_PICKUP]]
  $('.mypa-location-time').html('- Vanaf 08.30 uur')
  $('#mypa-pickup-express').prop 'checked', true
  return false

renderPickupLocation = (data)->
  displayOtherTab()
  $('#mypa-location-container').html ''

  for index, location of data
    html = """
      <div for='mypa-pickup-location-#{index}' class="mypa-row-lg">
        <input id="mypa-pickup-location-#{index}" type="radio" name="mypa-pickup-option" value='#{JSON.stringify location}'>
        <label for='mypa-pickup-location-#{index}' class='mypa-row-title'>
          <div class="mypa-checkmark mypa-main">
            <div class="mypa-circle"></div>
            <div class="mypa-checkmark-stem"></div>
            <div class="mypa-checkmark-kick"></div>
          </div>
          <span class="mypa-highlight">#{location.location}, <b>#{location.street} #{location.number}</b>,
          <i>#{String(Math.round(location.distance/100)/10).replace '.', ','} Km</i></span>
        </label>
        <span class='mypa-info'></span>
        <div class='mypa-opening-hours'>
          test
        </div>
      </div>
    """
    $('#mypa-location-container').append html

###
# Renders the available days for delivery
###
renderDays = (deliveryDays) ->
  deliveryDays.sort orderDays

  deliveryTimes = window.mypa.sortedDeliverytimes = {}
  $el = $('#mypa-tabs').html ''
  $('#mypa-delivery-options-container').width()

  for index, delivery of deliveryDays
    
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

  $('.mypa-tab').bind 'click', updateDelivery
  $('.mypa-tab')[0].click()

  $el.width deliveryDays.length * 105
  makeSlider()

updateDelivery = (e)->

  date = $("##{$(e.currentTarget).prop 'for'}")[0].value

  $('#mypa-delivery-options').html ''

  html = ''
  for index, time of window.mypa.sortedDeliverytimes[date]
    
    price = window.mypa.settings.price[time.price_comment]
    price ?= 'GRATIS'
    json =
      date: date
      time: [time]
    
    html += """
      <label for="mypa-time-#{index}" class='mypa-row-subitem'>
        <input id='mypa-time-#{index}' type="radio" name="mypa-delivery-time" value='#{JSON.stringify json}'>
        <label for="mypa-time-#{index}" class="mypa-checkmark">
          <div class="mypa-circle mypa-circle-checked"></div>
          <div class="mypa-checkmark-stem"></div>
          <div class="mypa-checkmark-kick"></div>
        </label>
        <span class="mypa-highlight">#{moment(time.start, 'HH:mm:SS').format 'H.mm'} - #{moment(time.end, 'HH:mm:SS').format 'H.mm'} uur</span>
        <span class='mypa-price'>#{price}</span>
      </label>
    """

  html += """
    <label for="mypa-myonoffswitch" class='mypa-row-subitem'>
      <input type="checkbox" name="mypa-onoffswitch" class="mypa-onoffswitch-checkbox" id="mypa-myonoffswitch">
      <div class="mypa-switch-container">
        <div class="mypa-onoffswitch">
          <label class="mypa-onoffswitch-label" for="mypa-myonoffswitch">
            <span class="mypa-onoffswitch-inner"></span>
           <span class="mypa-onoffswitch-switch"></span>
          </label>
        </div>
      </div>
      <span>Bij niet thuis, niet bij de buren bezorgen<span class='mypa-price'>&#8364 0,50</span></span>
    </label>
  """
  $('#mypa-delivery-options').html html

###
# Initializes the slider
###
makeSlider = ->
  slider = window.mypa.slider = {}
  slider.barLength = $('#mypa-tabs-container').outerWidth()
  slider.bars = $('#mypa-tabs').outerWidth() / slider.barLength
  slider.currentBar = 0

  $('#mypa-date-slider-right').removeClass 'mypa-slider-disabled'
  $('#mypa-date-slider-left').unbind().bind 'click', slideLeft
  $('#mypa-date-slider-right').unbind().bind 'click', slideRight

###
# Event handler for sliding the date slider to the left
###
slideLeft = (e)->
  slider = window.mypa.slider
  if slider.currentBar is 1
    $(e.currentTarget).addClass 'mypa-slider-disabled'
  else if slider.currentBar < 1
    return false
  else
    $(e.currentTarget).removeClass 'mypa-slider-disabled'

  $('#mypa-date-slider-right').removeClass 'mypa-slider-disabled'
  slider.currentBar--
  $el = $ '#mypa-tabs'
  left = slider.currentBar * slider.barLength * -1
  left = parseInt(left / 104.0) * 104
  $el.css left: left

###
# Event handler for sliding the date slider to the right
###
slideRight = (e)->
  slider = window.mypa.slider
  if parseInt(slider.currentBar) is parseInt slider.bars - 1
    $(e.currentTarget).addClass 'mypa-slider-disabled'
  else if slider.currentBar >= slider.bars - 1
    return false
  else
    $(e.currentTarget).removeClass 'mypa-slider-disabled'

  $('#mypa-date-slider-left').removeClass 'mypa-slider-disabled'
  slider.currentBar++
  $el = $ '#mypa-tabs'
  left = slider.currentBar * slider.barLength * -1
  left = parseInt(left / 104.0) * 104
  $el.css left: left

###
# Order function for the delivery array 
###
orderDays = (dayA, dayB) ->
  dateA = moment dayA.date
  dateB = moment dayB.date

  max = moment.max dateA, dateB

  return 1 if max is dateA

  return -1

initialize = ->
  moment.locale NATIONAL
  fetchDeliveryOptions()
  $('#mypa-back-arrow').bind 'click', displayOtherTab
  return null
  
$ document
  .ready initialize