<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly ?>

<template id="postnl-template">
    <style>
        body{
            background-color: white !important;
            word-wrap: break-word;
        }

        input[name=post-delivery-type],
        input[name=post-delivery-time],
        input[name=post-pickup-option]{
            display: none;
        }

        #post-slider{
            width: 204%;
            /*height: 100%;*/
        }

        .post-slider-pos-0{
            left:0;
        }

        .post-slider-pos-1{
            left: -49%;
        }

        .post-tab-container{
            vertical-align: top;
            transition: left 0.5s ease-out;
            width: 49%;
            /*height: 100%;*/
            display: inline-block;
            position:relative;
            overflow:hidden;
        }

        #post-delivery-options-container{
            position: relative;
            word-wrap: initial;
            font-size: 14px;
            display: inline-block;
            overflow: hidden;
            font-weight: 400;
            width: 100%;
            /*height: 100%;*/
        }

        .post-delivery-header, .post-tab{
            color: #fff;
        }

        #post-tabs{
            position: relative;
            display: block;
            width: 100%;
            transition: left 0.3s ease-out 0.1s;
        }

        .post-tab{
            transition: background-color 0.4s;
            font-size: 12px;
            background: #f7a027;
            padding: 5px 10px 5px 10px;
            display: inline-block;
            text-align: center;
            width: 80px;
            min-height:34px;
        }

        .post-date{
            display:none;
        }

        .post-date:checked+label , .post-tab:hover{
            background: #ed8c00;
        }

        .post-content-lg {
            transition: max-height 0.4s ease-out 0s;
            overflow:hidden;
        }

        .post-content-lg > div:first-child{
            border-top: solid 1px #D2D2D2;
        }

        .post-tab + .post-tab{
            margin-left: 4px;
        }

        .post-delivery-header{
            padding: 17px;
            font-size: 20px;
            background: #ed8c00;
        }

        .post-checkmark {
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

        .post-circle {
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

        .post-circle:hover, label.post-row-subitem:hover .post-circle{
            background-color: #3440b6;
        }

        input:checked + label.post-checkmark div.post-circle,
        input[name=post-delivery-type]:checked + label div.post-main div.post-circle,
        input[name=post-pickup-option]:checked + label div.post-main div.post-circle
        {
            background-color: #3440b6;
            z-index:0;
        }

        input[name=post-delivery-type] ~ div.post-content-lg{
            max-height: 0px;
        }

        input:disabled ~ div.post-switch-container label.post-onoffswitch-label span.post-onoffswitch-switch{
            background: #c7c7c7;
        }

        input[name=post-delivery-type]:checked ~ div.post-content-lg{
            max-height: 240px;
        }

        .post-checkmark-stem {
            position: absolute;
            width: 10%;
            height: 55%;
            background-color:#fff;
            left: 55%;
            top: 18%;
        }

        .post-checkmark-kick {
            position: absolute;
            width: 32%;
            height: 9%;
            background-color:#fff;
            left: 32%;
            top: 64%;
        }

        .post-row-lg{
            border-bottom: 1px solid #D2D2D2;
        }

        .post-row-title{
            display: inline-block;
            padding: 9px 15px 16px 15px;
            width: 80%
        }

        .post-row-subitem{
            margin: 2px 0 10px 40px;
            display: block;
        }


        #post-delivery-options{
        }

        .post-onoffswitch {
            top: 7px;
            position: relative; width: 45px;
            -webkit-user-select:none; -moz-user-select:none; -ms-user-select: none;
        }

        .post-onoffswitch-checkbox {
            display: none;
        }

        .post-onoffswitch-label {
            display: block; overflow: hidden; cursor: pointer;
            border: 2px solid #C7C7C7; border-radius: 20px;
        }

        .post-onoffswitch-inner {
            display: block; width: 200%; margin-left: -100%;
            transition: margin 0.3s ease-in 0s;
            margin-left: 0;
        }

        .post-onoffswitch-inner:before, .post-onoffswitch-inner:after {
            display: block; float: left; width: 50%; height: 20px; padding: 0; line-height: 20px;
            font-size: 14px; color: white; font-weight: bold;
            box-sizing: border-box;
        }

        .post-onoffswitch-inner:before {
            content: "N";
            padding-left: 7px;
            background-color: #DE0D0D; color: #FFFFFF;
        }

        .post-onoffswitch-inner:after {
            content: "J";
            padding-right: 7px;
            background-color: #39D12E; color: #FFFFFF;
            text-align: right;
        }

        .post-onoffswitch-switch {
            display: block; width: 20px; margin: 0px;
            background: #FFFFFF;
            position: absolute; top: 0; bottom: 0;
            right: 0px;
            border: 2px solid #C7C7C7; border-radius: 20px;
            transition: all 0.3s ease-in 0s;
        }

        .post-onoffswitch-checkbox:checked + div.post-switch-container .post-onoffswitch .post-onoffswitch-label .post-onoffswitch-inner {
            margin-left: -41px;
        }

        .post-onoffswitch-checkbox:checked + div.post-switch-container .post-onoffswitch .post-onoffswitch-label .post-onoffswitch-switch {
            right: 21px;
        }

        .post-switch-container{
            display: inline-block
        }

        input:checked ~ .post-highlight,
        input:checked ~ label.post-row-title span.post-highlight{
            color: #3440b6
        }

        #post-back-arrow{
            cursor: pointer;
        }

        .post-arrow-left::before {
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

        .post-arrow-right::before {
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

        .post-arrow-clickable:hover::before{
            border-left: 0.2em solid #3440b6;
            border-bottom: 0.2em solid #3440b6;
        }

        .post-arrow-clickable:hover{
            color: #3440b6;
        }

        #post-date-slider-left::before, #post-date-slider-right::before{
            border-color: #A0A0A0;
        }

        #post-date-slider-left:hover::before, #post-date-slider-right:hover::before{
            border-color: #3440b6;
        }

        .post-slider-disabled#post-date-slider-left::before,
        .post-slider-disabled#post-date-slider-right::before{
            border-color: #EAEAEA;
            cursor: not-allowed;
        }

        .post-date-slider-button {
            position: absolute;
            display: inline-block;
            top: 13px;
            font-size: 22px;
        }

        #post-date-slider-left{
            left: 19px;
        }

        #post-date-slider-right{
            right: 5px;
        }

        #post-tabs-container{
            height: 44px;
            margin-left: 45px;
            margin-right: 41px;
            overflow: hidden;
        }

        #post-location-container {
            overflow-x: auto;
            max-height: 270px;
            margin-right: 4px;
        }

        .post-price {
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

        .post-combination-price{
            padding: 2px 0 2px 0;
        }

        .post-combination-price.post-combination-price-active{
        }

        .post-combination-price > .post-price{
            margin: 21px 30px 0 0;
        }

        input:checked ~ .post-price,
        input:checked ~ span span.post-price,
        .post-price-active{
            background: #3440b6
        }

        .post-info{
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

        .post-info::before{
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

        .post-info::after{
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

        .post-opening-hours{
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

        div.afhalen-right:hover + .post-opening-hours{
            display: initial;
        }

        .post-day-of-the-week{
            display: inline-block;
            width: 30px;
        }

        .post-opening-hours-list{
            display: inline-block;
        }

        .post-hidden{
            display: none;
        }

        .post-overlay{
            position:absolute;
            height:100%;
            width:100%;
            z-index:100;
            background: rgba(226, 226, 226, 0.75);
        }

        #post-no-options{
            color: white;
            position: relative;
            z-index: 12;
            background: #ed8c00;
            padding: 20px 20px;
            margin: 0 auto;
            top: 13%;
            width: 217px;
            display: block;
            font-size: 20px;
        }

        .post-address{
            font-style: italic;
            color: darkorange;
            cursor: pointer;
        }

        .post-address:hover{
            color:#b36200;
            text-decoration:underline;
        }

        .post-inline-block{
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
        <?php if (isset(WooCommerce_PostNL()->checkout_settings['deliverydays_window']) && WooCommerce_PostNL()->checkout_settings['deliverydays_window'] == 0): ?>
        #post-tabs-container,
        .post-date-slider-button {
            display: none;
        }
        <?php endif ?>
        <?php if (!empty(WooCommerce_PostNL()->checkout_settings['base_color'])): $base_color = WooCommerce_PostNL()->checkout_settings['base_color']; ?>
        .post-tab{
            background-color: <?php echo $base_color;?>;
            opacity: .5;
        }
        .post-delivery-header,
        .post-date:checked+label, .post-tab:hover {
            background: <?php echo $base_color;?>;
            opacity: 1;
        }
        .post-address {
            color: <?php echo $base_color;?>;
        }
        .edit-tip > div {
            border-top-color: <?php echo $base_color;?>;
        }
        .edit-stem {
            background-color: <?php echo $base_color;?>;
        }
        #post-no-options {
            background: <?php echo $base_color;?>;
        }
        <?php endif ?>
        <?php if (!empty(WooCommerce_PostNL()->checkout_settings['highlight_color'])): $highlight_color = WooCommerce_PostNL()->checkout_settings['highlight_color']; ?>
        input:checked ~ .post-highlight, input:checked ~ label.post-row-title span.post-highlight,
        .post-arrow-clickable:hover {
            color: <?php echo $highlight_color; ?>;
        }
        input:checked + label.post-checkmark div.post-circle, input[name=post-delivery-type]:checked + label div.post-main div.post-circle, input[name=post-pickup-option]:checked + label div.post-main div.post-circle,
        .post-circle:hover, label.post-row-subitem:hover .post-circle,
        input:checked ~ .post-price, input:checked ~ span span.post-price {
            background-color: <?php echo $highlight_color; ?>;
        }
        .post-arrow-clickable:hover::before {
            border-left: 0.2em solid <?php echo $highlight_color;?>;
            border-bottom: 0.2em solid <?php echo $highlight_color;?>;
        }
        input:checked ~ .post-price, input:checked ~ span span.post-price, .post-price-active {
            background: <?php echo $highlight_color;?>;
        }
        .edit-location {
            color: <?php echo $highlight_color;?>;
        }
        <?php endif ?>
        <?php
		if (!empty(WooCommerce_PostNL()->checkout_settings['custom_css'])) {
		  echo WooCommerce_PostNL()->checkout_settings['custom_css'];
		}
		?>
    </style>
    <div id='post-delivery-options-container'>
        <div class="post-overlay">
            <span id="post-no-options">Geen adres opgegeven</span>
        </div>
        <div id="post-slider">
            <!-- First frame -->
            <div id="post-delivery-type-selection" class="post-tab-container post-slider-pos-0">
                <div id="post-date-slider-left" class="post-arrow-left post-back-arrow post-date-slider-button post-slider-disabled"></div>
                <div id="post-date-slider-right" class="post-arrow-right myapa-next-arrow post-date-slider-button post-slider-disabled"></div>
                <div id="post-tabs-container">
                    <div id='post-tabs'>
                    </div>
                </div>
                <div class='post-delivery-content post-container-lg'>
                    <div class='post-header-lg post-delivery-header'>
                        <span><b>BEZORGOPTIES</b></span> <span class="post-location"></span>
                    </div>
                    <div id='post-delivery-body'>
                        <div id='post-delivery-row' class='post-row-lg'>
                            <input id='post-delivery-option-check' type="radio" name="post-delivery-type" checked>
                            <label id='post-delivery-options-title' class='post-row-title' for="post-delivery-option-check">
                                <div class="post-checkmark post-main">
                                    <div class="post-circle post-circle-checked"></div>
                                    <div class="post-checkmark-stem"></div>
                                    <div class="post-checkmark-kick"></div>
                                </div>
                                <span class="post-highlight">Thuis of op het werk bezorgd</span>
                            </label>
                            <div id='post-delivery-options' class='post-content-lg'>
                            </div>
                        </div>
                        <div id='post-pickup-row' class='post-row-lg'>
                            <input type="radio" name="post-delivery-type" id="post-pickup-location">
                            <label id='post-pickup-options-title' class='post-row-title' for="post-pickup-location">
                                <div class="post-checkmark post-main">
                                    <div class="post-circle"></div>
                                    <div class="post-checkmark-stem"></div>
                                    <div class="post-checkmark-kick"></div>
                                </div>
                                <span class="post-highlight">Ophalen bij een PostNL locatie</span>
                            </label>
                            <div id='post-pickup-options-content' class='post-content-lg'>
                                <div>
                                    <label for='post-pickup' class='post-row-subitem post-pickup-selector'>
                                        <input id='post-pickup' type="radio" name="post-delivery-time">
                                        <label for="post-pickup" class="post-checkmark">
                                            <div class="post-circle"></div>
                                            <div class="post-checkmark-stem"></div>
                                            <div class="post-checkmark-kick"></div>
                                        </label>
                                        <span class="post-highlight">Vanaf 16.00 uur</span><span class='post-address' id="post-pickup-address"></span>
                                        <div class="edit">
                                            <div class="edit-stem">
                                            </div>
                                            <div class="edit-tip">
                                                <div>
                                                </div>
                                            </div>
                                        </div>
                                        <span class='post-price post-pickup-price'></span>
                                    </label>
                                    <label for='post-pickup-express' class='post-row-subitem post-pickup-selector'>
                                        <input id='post-pickup-express' type="radio" name="post-delivery-time">
                                        <label for='post-pickup-express' class="post-checkmark">
                                            <div class="post-circle post-circle-checked"></div>
                                            <div class="post-checkmark-stem"></div>
                                            <div class="post-checkmark-kick"></div>
                                        </label>
                                        <span class="post-highlight">Vanaf 8.30 uur</span><span class='post-address' id="post-pickup-express-address"></span>
                                        <div class="edit">
                                            <div class="edit-stem">
                                            </div>
                                            <div class="edit-tip">
                                                <div>
                                                </div>
                                            </div>
                                        </div>
                                        <span class='post-price post-pickup-express-price'></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="post-location-selector" class="post-tab-container post-slider-pos-0">
                <!-- Second frame -->
                <div id='post-tabs-2'>
                </div>
                <div class='post-container-lg post-delivery-content'>
                    <div class='post-header-lg post-delivery-header'>
                        <span id='post-back-arrow'><b>AFHALEN </b><span class="post-location-time"></span></span>
                    </div>
                    <div id="post-location-container">

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>