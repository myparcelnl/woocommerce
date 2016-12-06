<template id="myparcel-template">
  <style>
    body{
      background-color: white !important;
      word-wrap: break-word;
    }

    input[name=mypa-delivery-type],
    input[name=mypa-delivery-time],
    input[name=mypa-pickup-option]{
      display: none;
    }

    #mypa-slider{
      width: 204%;
      /*height: 100%;*/
    }

    .mypa-slider-pos-0{
      left:0;
    }

    .mypa-slider-pos-1{
      left: -49%;
    }

    .mypa-tab-container{
      vertical-align: top;
      transition: left 0.5s ease-out;
      width: 49%;
      /*height: 100%;*/
      display: inline-block;
      position:relative;
      overflow:hidden;
    }

    #mypa-delivery-options-container{
      position: relative;
      word-wrap: initial;
      font-size: 14px;
      display: inline-block;
      overflow: hidden;
      font-weight: 400;
      width: 100%;
      /*height: 100%;*/
    }

    .mypa-delivery-header, .mypa-tab{
      color: #fff;
    }

    #mypa-tabs{
      position: relative;
      display: block;
      width: 100%;
      transition: left 0.3s ease-out 0.1s;
    }

    .mypa-tab{
      transition: background-color 0.4s;
      font-size: 12px;
      background: #75D3D8;
      padding: 5px 10px 5px 10px;
      display: inline-block;
      text-align: center;
      width: 80px;
      min-height:34px;
    }

    .mypa-date{
      display:none;
    }

    .mypa-date:checked+label , .mypa-tab:hover{
      background: #01bbc5;
    }

    .mypa-content-lg {
      transition: max-height 0.4s ease-out 0s;
      overflow:hidden;
    }

    .mypa-content-lg > div:first-child{
      border-top: solid 1px #D2D2D2;
    }

    .mypa-tab + .mypa-tab{
      margin-left: 4px;
    }

    .mypa-delivery-header{
      padding: 17px;
      font-size: 20px;
      background: #01bbc5;
    }

    .mypa-checkmark {
      cursor: pointer;
      position: relative;
      top: 6px;
      display:inline-block;
      margin-right: 5px;
      width: 22px;
      height:22px;
      -ms-transform: rotate(45deg); /* IE 9 */
      -webkit-transform: rotate(45deg); /* Chrome, Safari, Opera */
      transform: rotate(45deg);
    }

    .mypa-circle {
      transition: background-color 0.4s;
      position: relative;
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background-color: #C7C7C7;
      left:0;
      top:0;
      z-index:1;
    }

    .mypa-circle:hover, label.mypa-row-subitem:hover .mypa-circle{
      background-color: #ff8c00;
    }

    input:checked + label.mypa-checkmark div.mypa-circle,
    input[name=mypa-delivery-type]:checked + label div.mypa-main div.mypa-circle,
    input[name=mypa-pickup-option]:checked + label div.mypa-main div.mypa-circle
    {
      background-color: #ff8c00;
      z-index:0;
    }

    input[name=mypa-delivery-type] ~ div.mypa-content-lg{
      max-height: 0px;
    }

    input:disabled ~ div.mypa-switch-container label.mypa-onoffswitch-label span.mypa-onoffswitch-switch{
      background: #c7c7c7;
    }

    input[name=mypa-delivery-type]:checked ~ div.mypa-content-lg{
      max-height: 240px;
    }

    .mypa-checkmark-stem {
      position: absolute;
      width: 10%;
      height: 55%;
      background-color:#fff;
      left: 55%;
      top: 18%;
    }

    .mypa-checkmark-kick {
      position: absolute;
      width: 32%;
      height: 9%;
      background-color:#fff;
      left: 32%;
      top: 64%;
    }

    .mypa-row-lg{
      border-bottom: 1px solid #D2D2D2;
    }

    .mypa-row-title{
      display: inline-block;
      padding: 9px 15px 16px 15px;
      width: 80%
    }

    .mypa-row-subitem{
      margin: 2px 0 10px 40px;
      display: block;
    }


    #mypa-delivery-options{
    }

    .mypa-onoffswitch {
      top: 7px;
      position: relative; width: 45px;
      -webkit-user-select:none; -moz-user-select:none; -ms-user-select: none;
    }

    .mypa-onoffswitch-checkbox {
      display: none;
    }

    .mypa-onoffswitch-label {
      display: block; overflow: hidden; cursor: pointer;
      border: 2px solid #C7C7C7; border-radius: 20px;
    }

    .mypa-onoffswitch-inner {
      display: block; width: 200%; margin-left: -100%;
      transition: margin 0.3s ease-in 0s;
      margin-left: 0;
    }

    .mypa-onoffswitch-inner:before, .mypa-onoffswitch-inner:after {
      display: block; float: left; width: 50%; height: 20px; padding: 0; line-height: 20px;
      font-size: 14px; color: white; font-weight: bold;
      box-sizing: border-box;
    }

    .mypa-onoffswitch-inner:before {
      content: "N";
      padding-left: 7px;
      background-color: #DE0D0D; color: #FFFFFF;
    }

    .mypa-onoffswitch-inner:after {
      content: "J";
      padding-right: 7px;
      background-color: #39D12E; color: #FFFFFF;
      text-align: right;
    }

    .mypa-onoffswitch-switch {
      display: block; width: 20px; margin: 0px;
      background: #FFFFFF;
      position: absolute; top: 0; bottom: 0;
      right: 0px;
      border: 2px solid #C7C7C7; border-radius: 20px;
      transition: all 0.3s ease-in 0s;
    }

    .mypa-onoffswitch-checkbox:checked + div.mypa-switch-container .mypa-onoffswitch .mypa-onoffswitch-label .mypa-onoffswitch-inner {
      margin-left: -41px;
    }

    .mypa-onoffswitch-checkbox:checked + div.mypa-switch-container .mypa-onoffswitch .mypa-onoffswitch-label .mypa-onoffswitch-switch {
      right: 21px;
    }

    .mypa-switch-container{
      display: inline-block
    }

    input:checked ~ .mypa-highlight,
    input:checked ~ label.mypa-row-title span.mypa-highlight{
      color: #FF8C00
    }

    #mypa-back-arrow{
      cursor: pointer;
    }

    .mypa-arrow-left::before {
      position: relative;
      content: "";
      display: inline-block;
      width: 0.6em;
      height: 0.6em;
      border-left: 0.2em solid #fff;
      border-bottom: 0.2em solid #fff;
      transform: rotate(45deg);
      margin-right: 0.5em;
    }

    .mypa-arrow-right::before {
      position: relative;
      content: "";
      display: inline-block;
      width: 0.6em;
      height: 0.6em;
      border-right: 0.2em solid #fff;
      border-top: 0.2em solid #fff;
      transform: rotate(45deg);
      margin-right: 0.5em;
    }

    .mypa-arrow-clickable:hover::before{
      border-left: 0.2em solid #ff8c00;
      border-bottom: 0.2em solid #ff8c00;
    }

    .mypa-arrow-clickable:hover{
      color: #ff8c00;
    }

    #mypa-date-slider-left::before, #mypa-date-slider-right::before{
      border-color: #A0A0A0;
    }

    #mypa-date-slider-left:hover::before, #mypa-date-slider-right:hover::before{
      border-color: #FF8C00;
    }

    .mypa-slider-disabled#mypa-date-slider-left::before,
    .mypa-slider-disabled#mypa-date-slider-right::before{
      border-color: #EAEAEA;
      cursor: not-allowed;
    }

    .mypa-date-slider-button {
      position: absolute;
      display: inline-block;
      top: 13px;
      font-size: 22px;
    }

    #mypa-date-slider-left{
      left: 19px;
    }

    #mypa-date-slider-right{
      right: 5px;
    }

    #mypa-tabs-container{
      height: 44px;
      margin-left: 45px;
      margin-right: 41px;
      overflow: hidden;
    }

    #mypa-location-container {
      overflow-x: auto;
      max-height: 270px;
      margin-right: 4px;
    }

    .mypa-price {
      position: relative;
      top: 5px;
      display: inline-block;
      padding: 2px 5px;
      background: #C7C7C7;
      color: white;
      float: right;
      margin-right: 25px;
      font-weight: 800;
      font-size: 17px;
      margin-bottom: 10px;
    }

    .mypa-combination-price{
      padding: 2px 0 2px 0;
    }

    .mypa-combination-price.mypa-combination-price-active{
    }

    .mypa-combination-price > .mypa-price{
      margin: 21px 30px 0 0;
    }

    input:checked ~ .mypa-price,
    input:checked ~ span span.mypa-price,
    .mypa-price-active{
      background: #ff8c00
    }

    .mypa-info{
      display: inline-block;
      box-sizing: border-box;
      vertical-align: middle;
      position: relative;
      font-style: normal;
      color: #ddd;
      text-align: left;
      text-indent: -9999px;
      direction: ltr;
      width: 26px;
      height: 26px;
      border-radius: 50%;
      margin: 4px;
      border: 2px solid;
    }

    .mypa-info::before{
      position: absolute;
      left: 50%;
      -webkit-transform: translate(-50%,-50%);
      -ms-transform: translate(-50%,-50%);
      transform: translate(-50%,-50%);
      pointer-events: none;
      content: '';
      width: 2px;
      height: 9px;
      top: 35%;
      box-shadow: inset 0 0 0 32px;
      border-radius: 2px;
    }

    .mypa-info::after{
      pointer-events: none;
      content: '';
      width: 6px;
      height: 2px;
      -webkit-transform-origin: left center;
      -ms-transform-origin: left center;
      transform-origin: left center;
      -webkit-transform: rotate(45deg) translate(1px,2px);
      -ms-transform: rotate(45deg) translate(1px,2px);
      transform: rotate(45deg) translate(1px,2px);
      top: 35%;
      box-shadow: inset 0 0 0 32px;
      border-radius: 2px;
      position: absolute;
      left: 50%;
    }

    .mypa-opening-hours{
      display: none;
      position: absolute;
      top: 77px;
      right: 115px;
      padding: 10px;
      background-color: white;
      -webkit-box-shadow:  1px 1px 10px #B9B9B9;
      -moz-box-shadow:  1px 1px 10px #B9B9B9;
      box-shadow:  1px 1px 10px #B9B9B9;
    }

    div.afhalen-right:hover + .mypa-opening-hours{
      display: initial;
    }

    .mypa-day-of-the-week{
      display: inline-block;
      width: 30px;
    }

    .mypa-opening-hours-list{
      display: inline-block;
    }

    .mypa-hidden{
      display: none;
    }

    .mypa-overlay{
      position:absolute;
      height:100%;
      width:100%;
      z-index:100;
      background: rgba(226, 226, 226, 0.75);
    }

    #mypa-no-options{
      color: white;
      position: relative;
      z-index: 12;
      background: #01bbc5;
      padding: 20px 20px;
      margin: 0 auto;
      top: 13%;
      width: 217px;
      display: block;
      font-size: 20px;
    }

    .mypa-address{
      font-style: italic;
      color: darkorange;
      cursor: pointer;
    }

    .mypa-address:hover{
      color:#b36200;
      text-decoration:underline;
    }

    .mypa-inline-block{
      display: inline-block;
    }

    .afhalen-right{
      float: right;
      margin: 9px 4px 0 0;
     }

    .afhalen-check{
      float: left;
      clear: left;
     }

    .afhalen-tekst{
      margin: 0px 38px 10px 57px;
      padding-top: 18px;
     }

    .afhalen-row{
      min-height: 46px;
     }

    .edit-stem{
      background-color: darkorange;
      width:3px;
      height:15px;
      border-radius: 3px 3px 0 0px;
    }

    .edit-stem-top{
      border-bottom: 1px solid darkorange;
      height: 5px;
      width: 100%;
    }

    .edit-tip > div{
      width: 0; 
      height: 0; 
      border-left: 2px solid transparent;
      border-right: 2px solid transparent;
      border-top: 3px solid darkorange;
    }

    .edit-tip > div:before{
      position:relative;
      display: block;
      content: '';
      width: 0; 
      left: -2px;
      bottom: 3px;
      height: 0; 
      border-left: 2px solid transparent;
      border-right: 2px solid transparent;
      border-top: 2px solid white;
    }

    .edit{
      display:inline-block;
      position: relative;
      top: 12px;
      left: 6px;
      width:20px;
      height:20px;
      transform: rotate(45deg);
    }

  </style>

  <!-- CUSTOM STYLES / STYLE OVERRIDES -->
  <style>
  <?php if (isset(WooCommerce_MyParcel()->checkout_settings['deliverydays_window']) && WooCommerce_MyParcel()->checkout_settings['deliverydays_window'] == 0): ?>
  #mypa-tabs-container,
  .mypa-date-slider-button {
    display: none;
  }
  <?php endif ?>
  <?php if (!empty(WooCommerce_MyParcel()->checkout_settings['base_color'])): $base_color = WooCommerce_MyParcel()->checkout_settings['base_color']; ?>
  .mypa-tab{
    background-color: <?php echo $base_color;?>;
    opacity: .5;
  }
  .mypa-delivery-header,
  .mypa-date:checked+label, .mypa-tab:hover {
    background: <?php echo $base_color;?>;
    opacity: 1;
  }
  .mypa-address {
    color: <?php echo $base_color;?>;
  }
  .edit-tip > div {
    border-top-color: <?php echo $base_color;?>;
  }
  .edit-stem {
    background-color: <?php echo $base_color;?>;
  }
  #mypa-no-options {
    background: <?php echo $base_color;?>;
  }
  <?php endif ?>
  <?php if (!empty(WooCommerce_MyParcel()->checkout_settings['highlight_color'])): $highlight_color = WooCommerce_MyParcel()->checkout_settings['highlight_color']; ?>
  input:checked ~ .mypa-highlight, input:checked ~ label.mypa-row-title span.mypa-highlight,
  .mypa-arrow-clickable:hover {
    color: <?php echo $highlight_color; ?>;
  }
  input:checked + label.mypa-checkmark div.mypa-circle, input[name=mypa-delivery-type]:checked + label div.mypa-main div.mypa-circle, input[name=mypa-pickup-option]:checked + label div.mypa-main div.mypa-circle,
  .mypa-circle:hover, label.mypa-row-subitem:hover .mypa-circle,
  input:checked ~ .mypa-price, input:checked ~ span span.mypa-price {
    background-color: <?php echo $highlight_color; ?>;
  }
  .mypa-arrow-clickable:hover::before {
    border-left: 0.2em solid <?php echo $highlight_color;?>;
    border-bottom: 0.2em solid <?php echo $highlight_color;?>;
  }
  input:checked ~ .mypa-price, input:checked ~ span span.mypa-price, .mypa-price-active {
    background: <?php echo $highlight_color;?>;
  }
  .edit-location {
    color: <?php echo $highlight_color;?>;
  }
  <?php endif ?>
  <?php
  if (!empty(WooCommerce_MyParcel()->checkout_settings['custom_css'])) {
    echo WooCommerce_MyParcel()->checkout_settings['custom_css'];
  }
  ?>
  </style>
<div id='mypa-delivery-options-container'>
  <div class="mypa-overlay">
    <span id="mypa-no-options">Geen adres opgegeven</span>
  </div>
  <div id="mypa-slider">
    <!-- First frame -->
    <div id="mypa-delivery-type-selection" class="mypa-tab-container mypa-slider-pos-0">
      <div id="mypa-date-slider-left" class="mypa-arrow-left mypa-back-arrow mypa-date-slider-button mypa-slider-disabled"></div>
      <div id="mypa-date-slider-right" class="mypa-arrow-right myapa-next-arrow mypa-date-slider-button mypa-slider-disabled"></div>
      <div id="mypa-tabs-container">
        <div id='mypa-tabs'>
        </div>
      </div>
      <div class='mypa-delivery-content mypa-container-lg'>
        <div class='mypa-header-lg mypa-delivery-header'>
          <span><b>BEZORGOPTIES</b></span> <span class="mypa-location"></span>
        </div>
        <div id='mypa-delivery-body'>
          <div id='mypa-delivery-row' class='mypa-row-lg'>
            <input id='mypa-delivery-option-check' type="radio" name="mypa-delivery-type" checked>
            <label id='mypa-delivery-options-title' class='mypa-row-title' for="mypa-delivery-option-check">
              <div class="mypa-checkmark mypa-main">
                <div class="mypa-circle mypa-circle-checked"></div>
                <div class="mypa-checkmark-stem"></div>
                <div class="mypa-checkmark-kick"></div>
              </div>
              <span class="mypa-highlight">Thuis of op het werk bezorgd</span>
            </label>
            <div id='mypa-delivery-options' class='mypa-content-lg'>
            </div>
          </div>
          <div id='mypa-pickup-row' class='mypa-row-lg'>
            <input type="radio" name="mypa-delivery-type" id="mypa-pickup-location">
            <label id='mypa-pickup-options-title' class='mypa-row-title' for="mypa-pickup-location">
              <div class="mypa-checkmark mypa-main">
                <div class="mypa-circle"></div>
                <div class="mypa-checkmark-stem"></div>
                <div class="mypa-checkmark-kick"></div>
              </div>
              <span class="mypa-highlight">Ophalen bij een PostNL locatie</span>
            </label>
            <div id='mypa-pickup-options-content' class='mypa-content-lg'>
              <div>
                <label for='mypa-pickup' class='mypa-row-subitem mypa-pickup-selector'>
                  <input id='mypa-pickup' type="radio" name="mypa-delivery-time">
                  <label for="mypa-pickup" class="mypa-checkmark">
                    <div class="mypa-circle"></div>
                    <div class="mypa-checkmark-stem"></div>
                    <div class="mypa-checkmark-kick"></div>
                  </label>
                  <span class="mypa-highlight">Vanaf 16.00 uur</span><span class='mypa-address' id="mypa-pickup-address"></span>
                  <div class="edit">  
                    <div class="edit-stem">
                    </div>
                    <div class="edit-tip">
                      <div>
                      </div>
                    </div>
                  </div>
                  <span class='mypa-price mypa-pickup-price'></span>
                </label>
                <label for='mypa-pickup-express' class='mypa-row-subitem mypa-pickup-selector'>
                  <input id='mypa-pickup-express' type="radio" name="mypa-delivery-time">
                  <label for='mypa-pickup-express' class="mypa-checkmark">
                    <div class="mypa-circle mypa-circle-checked"></div>
                    <div class="mypa-checkmark-stem"></div>
                    <div class="mypa-checkmark-kick"></div>
                  </label>
                  <span class="mypa-highlight">Vanaf 8.30 uur</span><span class='mypa-address' id="mypa-pickup-express-address"></span>
                  <div class="edit">  
                    <div class="edit-stem">
                    </div>
                    <div class="edit-tip">
                      <div>
                      </div>
                    </div>
                  </div>
                  <span class='mypa-price mypa-pickup-express-price'></span>
                </label>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div id="mypa-location-selector" class="mypa-tab-container mypa-slider-pos-0">
      <!-- Second frame -->
      <div id='mypa-tabs-2'>
      </div>
      <div class='mypa-container-lg mypa-delivery-content'>
        <div class='mypa-header-lg mypa-delivery-header'>
          <span id='mypa-back-arrow'><b>AFHALEN </b><span class="mypa-location-time"></span></span>
        </div>
        <div id="mypa-location-container">

        </div>
      </div>
    </div>
  </div>
</div>
</template>