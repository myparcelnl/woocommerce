# Changelog

All notable changes to this project will be documented in this file. See
[Conventional Commits](https://conventionalcommits.org) for commit guidelines.

## [5.3.2](https://github.com/myparcelnl/woocommerce/compare/v5.3.1...v5.3.2) (2025-03-07)


### :bug: Bug Fixes

* **language:** fix myparcel settings not rendering in some languages ([#1259](https://github.com/myparcelnl/woocommerce/issues/1259)) ([fcf26d0](https://github.com/myparcelnl/woocommerce/commit/fcf26d0b9cfd6a66ced491777c4b7a9a58393380))
* **migrations:** fix an issue where db migrations would not automatically run for already active plugin installations ([#1256](https://github.com/myparcelnl/woocommerce/issues/1256)) ([49df9ed](https://github.com/myparcelnl/woocommerce/commit/49df9ed8d8629aa50f0c5b2cf4d3e3497b37ec3d))

## [5.3.1](https://github.com/myparcelnl/woocommerce/compare/v5.3.0...v5.3.1) (2025-02-13)


### :bug: Bug Fixes

* resolve errors relating to audits table ([#1228](https://github.com/myparcelnl/woocommerce/issues/1228)) ([ff6c2c4](https://github.com/myparcelnl/woocommerce/commit/ff6c2c4beff7c7f3eb2cdcfe4d98e26e9a2b6f0f))

## [5.3.0](https://github.com/myparcelnl/woocommerce/compare/v5.2.0...v5.3.0) (2025-01-30)


### :bug: Bug Fixes

* Make VAT and EORI fields optional for non-EU checkout ([#1219](https://github.com/myparcelnl/woocommerce/issues/1219)) ([1950afa](https://github.com/myparcelnl/woocommerce/commit/1950afa83b835e23611c32d987db88078021c53c))


### :sparkles: New Features

* add toggle for EU VAT and EORI fields in checkout settings ([#1227](https://github.com/myparcelnl/woocommerce/issues/1227)) ([4064129](https://github.com/myparcelnl/woocommerce/commit/406412998aa52d50319dd43e17aee83d4703784e))

## [5.2.0](https://github.com/myparcelnl/woocommerce/compare/v5.1.0...v5.2.0) (2025-01-03)


### :sparkles: New Features

* **deps:** upgrade myparcelnl/pdk to v2.49.1 ([bcc543e](https://github.com/myparcelnl/woocommerce/commit/bcc543e4bab98163b405241321e2b226e89fe4c9))

## [5.1.0](https://github.com/myparcelnl/woocommerce/compare/v5.0.0...v5.1.0) (2024-12-17)


### :zap: Performance Improvements

* properly externalize delivery options dependency ([9c332e1](https://github.com/myparcelnl/woocommerce/commit/9c332e12cf807d2a6e070b230c6382e1a7640e00))


### :bug: Bug Fixes

* make filters work correctly ([#1189](https://github.com/myparcelnl/woocommerce/issues/1189)) ([239878a](https://github.com/myparcelnl/woocommerce/commit/239878a3237e7e9f36395fbedd75f75bc2061bc7))
* **settings:** hide delivery options position setting when blocks checkout is enabled ([#1188](https://github.com/myparcelnl/woocommerce/issues/1188)) ([b6115c9](https://github.com/myparcelnl/woocommerce/commit/b6115c9951f762babec1b3150a1db70b9adbbf9a)), closes [#1189](https://github.com/myparcelnl/woocommerce/issues/1189)
* **webhooks:** prevent webhook callback never being executed ([#1187](https://github.com/myparcelnl/woocommerce/issues/1187)) ([d8cf0bb](https://github.com/myparcelnl/woocommerce/commit/d8cf0bb018626982883be6b08aa4ce51b753cb53))


### :sparkles: New Features

* **backend:** add download logs action ([#1175](https://github.com/myparcelnl/woocommerce/issues/1175)) ([ee4cecb](https://github.com/myparcelnl/woocommerce/commit/ee4cecbf29c5fb6b4cb9c62ffedc383e60864036))
* find order by api identifier ([#1214](https://github.com/myparcelnl/woocommerce/issues/1214)) ([a0f76cb](https://github.com/myparcelnl/woocommerce/commit/a0f76cbe73d30497a2ae66fb68f453f8fdbd2568))

## [5.0.0](https://github.com/myparcelnl/woocommerce/compare/v4.24.1...v5.0.0) (2024-10-31)


### ⚠ BREAKING CHANGES

* rebuild entire plugin with pdk

### :bug: Bug Fixes

* add default value for delivery options position filter ([97600e6](https://github.com/myparcelnl/woocommerce/commit/97600e64437f72a26a43904f9bf655bad5cdf3c7))
* add missing entry to container ([f4cb71b](https://github.com/myparcelnl/woocommerce/commit/f4cb71bb689836a1bd956b4d55658f521f0716ad))
* **admin:** add missing animations ([132da45](https://github.com/myparcelnl/woocommerce/commit/132da45d6ee2a2f1d2156b1ae4132aa23829457d))
* **admin:** add multi select input ([20e71a5](https://github.com/myparcelnl/woocommerce/commit/20e71a5c0d021ac8df30c775fe5bb564688a31d3))
* **admin:** change shipment label row transition ([c2d7cad](https://github.com/myparcelnl/woocommerce/commit/c2d7cad9027608c835c5cec2c1b7cb9942a5168d))
* **admin:** do not render untranslated subtext ([fb0ccce](https://github.com/myparcelnl/woocommerce/commit/fb0ccce444cee85a7ca12a22f89f1b9e90a7207f))
* **admin:** fix button dropdowns not expanding correctly ([#1129](https://github.com/myparcelnl/woocommerce/issues/1129)) ([6445513](https://github.com/myparcelnl/woocommerce/commit/6445513167d260cf3ee58556b13f4124b305b297))
* **admin:** fix code editor not being used ([60f6014](https://github.com/myparcelnl/woocommerce/commit/60f60140b21e81af1d9c7322fd9102ab2420d8b0))
* **admin:** fix console warning on modal open ([8040268](https://github.com/myparcelnl/woocommerce/commit/8040268d659a611c5127d79df935cca8302e355a))
* **admin:** fix cutoff time option throwing error ([8e84af9](https://github.com/myparcelnl/woocommerce/commit/8e84af99fc47022f0030725ed6459dff7dce19f2))
* **admin:** fix cutoff time translation ([87faab8](https://github.com/myparcelnl/woocommerce/commit/87faab8b44d52314626efca298d8a3f3446b05fe))
* **admin:** fix disabled and readonly logic on all inputs ([77a5492](https://github.com/myparcelnl/woocommerce/commit/77a549264923f07f93623c442752515581c26c55))
* **admin:** fix dropoff input not showing ([#1110](https://github.com/myparcelnl/woocommerce/issues/1110)) ([e2da027](https://github.com/myparcelnl/woocommerce/commit/e2da027c917ddef5b4a7b9f5421bdd20db9ea8c5))
* **admin:** fix import of shipment label wrapper ([69dc9fa](https://github.com/myparcelnl/woocommerce/commit/69dc9fa4a45672519582f9476927f50a0d617107))
* **admin:** fix input widths exceeding containers ([e3381db](https://github.com/myparcelnl/woocommerce/commit/e3381dbaa2713030ff1236fd1a18e4f3800dae95))
* **admin:** fix label description notice in order grid ([63b631e](https://github.com/myparcelnl/woocommerce/commit/63b631ed4e0bc2379905b7137cd21e7f16778876))
* **admin:** fix rendering on elements with undefined as ref value ([5332974](https://github.com/myparcelnl/woocommerce/commit/533297497b64caf2fc066e6a71b06311c31cfc6e))
* **admin:** fix select boxes duplicating and options not being preselected ([2afaf62](https://github.com/myparcelnl/woocommerce/commit/2afaf6216464a253a82b8556dcd2ea9a761f1ef4))
* **admin:** fix select inputs not showing initial value ([85ceb13](https://github.com/myparcelnl/woocommerce/commit/85ceb13a4ef9b7f11844ecdc84b43f6f3dc3c422))
* **admin:** fix tooltips not being initialized ([43cfc49](https://github.com/myparcelnl/woocommerce/commit/43cfc49f299615fb4a812d120443669ee34dbc3e))
* **admin:** improve accessibility ([be58218](https://github.com/myparcelnl/woocommerce/commit/be5821841475e53eabe6e39a5bacb0c6380e3177))
* **admin:** improve dropdown button ([578dadb](https://github.com/myparcelnl/woocommerce/commit/578dadb2089d6f226a7023c7640eb19722c2a694))
* **admin:** improve select inputs ([7aab3b1](https://github.com/myparcelnl/woocommerce/commit/7aab3b1a4a0b19bf96f2c90a4dcd86ffb35e2411))
* **admin:** initialize tooltips properly ([2f46db0](https://github.com/myparcelnl/woocommerce/commit/2f46db05cfb553babdbe4975f38ccf07b362163c))
* **admin:** make button groups responsive ([#1003](https://github.com/myparcelnl/woocommerce/issues/1003)) ([654b8b9](https://github.com/myparcelnl/woocommerce/commit/654b8b9a9c543cdb288779a14691f26a26098b9f))
* **admin:** make the boxes and modals match wordpress better ([#1001](https://github.com/myparcelnl/woocommerce/issues/1001)) ([e95b663](https://github.com/myparcelnl/woocommerce/commit/e95b66344be5f44b9a848f46499b95378ce5e7a6))
* **admin:** show correct styling in settings page ([#1036](https://github.com/myparcelnl/woocommerce/issues/1036)) ([dbb4be1](https://github.com/myparcelnl/woocommerce/commit/dbb4be15ca6e39f62c0574f625204bb4425928fe))
* **admin:** translate form group text correctly ([e0ba9c0](https://github.com/myparcelnl/woocommerce/commit/e0ba9c0627aee8768aa68a789614f1571c8ae1fc))
* **admin:** update button style ([184b4b4](https://github.com/myparcelnl/woocommerce/commit/184b4b41c75a4f5b135ec7c60dacf6ef7c0365ea))
* **admin:** update components ([ba5f874](https://github.com/myparcelnl/woocommerce/commit/ba5f874969adf1b1f96ba8e95abb5e2431847e14))
* **admin:** update form group ([8a2e5ee](https://github.com/myparcelnl/woocommerce/commit/8a2e5eeaaeec9be1e8692a360ca6beb28116be03))
* **admin:** use different log level in production mode ([d2538f0](https://github.com/myparcelnl/woocommerce/commit/d2538f0d81ef74354d60471876c7918420a35ad7))
* **admin:** use the correct versions of js dependencies ([2eefa0e](https://github.com/myparcelnl/woocommerce/commit/2eefa0e012668af9d987606fc9e07a2124484812))
* **build:** fix plugin not being seen as new version of 4.x ([f832abb](https://github.com/myparcelnl/woocommerce/commit/f832abb8fba0e80f989e1542b23855caa70844a6))
* **cart:** fix incorrect reference to delivery options fees service ([27b0776](https://github.com/myparcelnl/woocommerce/commit/27b0776ccf76ed3112f7d9d7c97b87a44fbe2848))
* **checkout:** disable eori field warnings when they don't exist ([adc7527](https://github.com/myparcelnl/woocommerce/commit/adc7527699e4409fa5f02b14789c1b7c267c20c4))
* **checkout:** fix delivery options not being passed to backend ([33288e8](https://github.com/myparcelnl/woocommerce/commit/33288e8913ebd3d5c0a2f06a48357d522ff41f3c))
* **checkout:** fix delivery options not being saved to order ([e69766e](https://github.com/myparcelnl/woocommerce/commit/e69766e37588ee1b3cce8a0722e0e462d085e70a))
* **checkout:** fix empty billing address 1 ([#1005](https://github.com/myparcelnl/woocommerce/issues/1005)) ([b6ac3cc](https://github.com/myparcelnl/woocommerce/commit/b6ac3cc2ccc8dac291c7516caac6afd48430f406))
* **checkout:** fix error on submitting order ([dae7a45](https://github.com/myparcelnl/woocommerce/commit/dae7a459c171b05340ec420e89d59ead7539b443))
* **checkout:** fix javascript error on order received page ([c0477cc](https://github.com/myparcelnl/woocommerce/commit/c0477cc80aef2804de2dee23b9276dd85b4ee0d2))
* **checkout:** fix third party shipping methods not working with delivery options ([80357fc](https://github.com/myparcelnl/woocommerce/commit/80357fc7a6e7fc01d8e4e06faa855e736211da19))
* **checkout:** have delivery options show in classic checkout ([#1094](https://github.com/myparcelnl/woocommerce/issues/1094)) ([4812cca](https://github.com/myparcelnl/woocommerce/commit/4812cca4d76fcedb103fe2088327c856336af188))
* **checkout:** improve checkout logic ([26e3ee1](https://github.com/myparcelnl/woocommerce/commit/26e3ee14b411c1d7e268ebdede24fa70d0696149))
* **checkout:** prevent orders without incomplete address ([e416558](https://github.com/myparcelnl/woocommerce/commit/e4165584738eb287a0c036af3b5421972342c4e3))
* **checkout:** use show delivery options on backorders setting ([#1136](https://github.com/myparcelnl/woocommerce/issues/1136)) ([9495554](https://github.com/myparcelnl/woocommerce/commit/94955541e71bb4a7ad479fef0d3955d18a722fbd))
* **compatibility:** reduce conflicts with other plugins ([#1025](https://github.com/myparcelnl/woocommerce/issues/1025)) ([4b90766](https://github.com/myparcelnl/woocommerce/commit/4b907665e3bb4055c892bfc590a2d27a9f82303f))
* **components:** improve components ([942f0f3](https://github.com/myparcelnl/woocommerce/commit/942f0f33742f6e5c9b99f71c74d94b8e9e75d0c2))
* correctly show track & trace in account page ([ecb4fd8](https://github.com/myparcelnl/woocommerce/commit/ecb4fd8d529ab62892b1457b56645d4d46c82f3e))
* create audits table on installation ([#1068](https://github.com/myparcelnl/woocommerce/issues/1068)) ([7cc0b25](https://github.com/myparcelnl/woocommerce/commit/7cc0b25800c41f4d8e549648119d32c7503c4983))
* **cronservice:** make callable usable ([#1052](https://github.com/myparcelnl/woocommerce/issues/1052)) ([11882b0](https://github.com/myparcelnl/woocommerce/commit/11882b067287d51c37f163b99cf29a56c7501a11))
* **customs-declaration:** pass correct package contents ([#1012](https://github.com/myparcelnl/woocommerce/issues/1012)) ([dde84e6](https://github.com/myparcelnl/woocommerce/commit/dde84e67b5ed7b24f62192e0c8c12defb7d4881a))
* **customs-declarations:** fix items amount ([#1010](https://github.com/myparcelnl/woocommerce/issues/1010)) ([545a8f1](https://github.com/myparcelnl/woocommerce/commit/545a8f19288b365585e20c029a3da7f51886d956))
* disable plugin peacefully when prerequisites are not met ([d891414](https://github.com/myparcelnl/woocommerce/commit/d8914140cff5df8c74cc8b7e5df2f1716c09081e))
* disable selectwoo for now because it won't work ([d20972e](https://github.com/myparcelnl/woocommerce/commit/d20972e3823bf3f6794e78d0d2b5b5abd5feadc8))
* do not show product settings in custom fields ([c21f951](https://github.com/myparcelnl/woocommerce/commit/c21f9512ab8e2ff6ad980dcde891977535268107))
* ensure numeric values at physical properties ([44e96f8](https://github.com/myparcelnl/woocommerce/commit/44e96f819d00cf7b34be0b3b3a4e1a78d80ba638))
* **export:** export label description ([ced065d](https://github.com/myparcelnl/woocommerce/commit/ced065d3533d8a1db2128bc2b12d58ec990986fc))
* **export:** fix multiple automatic exports ([#1034](https://github.com/myparcelnl/woocommerce/issues/1034)) ([bc610ad](https://github.com/myparcelnl/woocommerce/commit/bc610adf8e9a05be800b4e9c53da43f1951f5352))
* fill settings that didnt't exist before migration ([bab3a36](https://github.com/myparcelnl/woocommerce/commit/bab3a3661e099206e0df652ce6f875acc2416d24))
* **filters:** allow not having a default value ([c580358](https://github.com/myparcelnl/woocommerce/commit/c580358b56e8cb9cd1dd3453933af9647601f232))
* fix changes in settings and account not being updated correctly ([514d990](https://github.com/myparcelnl/woocommerce/commit/514d9904e1e0fe49242a972f9f3e6909a94cd07b))
* fix custom fields not showing when they should ([8b1a3e1](https://github.com/myparcelnl/woocommerce/commit/8b1a3e1caeb1d70d18263230823ebbf50f0ad2f2))
* fix fatal error caused by wrongly prefixed file ([96fe2b7](https://github.com/myparcelnl/woocommerce/commit/96fe2b7eb4054b691c3edd3f50e75c84b839d291))
* fix imports ([d8a49ce](https://github.com/myparcelnl/woocommerce/commit/d8a49ce10fa70b27835ae71176856bd47ccc6e23))
* fix myparcelbe version not working ([c9eaba5](https://github.com/myparcelnl/woocommerce/commit/c9eaba57dacb499bde695f9bcd21f3e4a85b1c23))
* fix naming error in bulk actions order mode ([4a7c17b](https://github.com/myparcelnl/woocommerce/commit/4a7c17b5c9ab7a71d520c58676722ffc2d12daf5))
* fix number error on "add order" page ([adadd92](https://github.com/myparcelnl/woocommerce/commit/adadd923aefebe557a2ad174970cb2b3be873973))
* fix ts imports ([#1030](https://github.com/myparcelnl/woocommerce/issues/1030)) ([c6c2afc](https://github.com/myparcelnl/woocommerce/commit/c6c2afc2aef1b8f4b25bd3e3724ab597c1ee706d))
* fix undefined index warning ([7b82dd4](https://github.com/myparcelnl/woocommerce/commit/7b82dd45a87fad3f22ba77893d4f9a12ca1b3ec6))
* fix width of carrier settings not matching other tabs ([#1000](https://github.com/myparcelnl/woocommerce/issues/1000)) ([2f52c19](https://github.com/myparcelnl/woocommerce/commit/2f52c19bb9e70fa636644975f69cf6e85b420a2c))
* **hooks:** fix error on getting pdk frontend routes ([e0973e8](https://github.com/myparcelnl/woocommerce/commit/e0973e892b9047d7ea92b936c0dafd63421eb27f))
* **hpos:** fix order grid bulk actions not working ([d1eef35](https://github.com/myparcelnl/woocommerce/commit/d1eef35bca6681736d2b349ef37e4371378378ae))
* implement bootstrapper ([26feee5](https://github.com/myparcelnl/woocommerce/commit/26feee5f94896ca3de1b106b02da59c8ba61380e))
* improve components ([3a276a9](https://github.com/myparcelnl/woocommerce/commit/3a276a95a4dddf0d87977177316f7fe640bcffb7))
* improve prerequisites logic ([60b65f7](https://github.com/myparcelnl/woocommerce/commit/60b65f7780c4b39c38cf6a2756f3258b5fb506ab))
* increase order list column width ([73b8980](https://github.com/myparcelnl/woocommerce/commit/73b8980cf64f36cece74697cd98259fc2148f3a6))
* **meta:** fix settings link not showing on plugins page ([1ea10bc](https://github.com/myparcelnl/woocommerce/commit/1ea10bca4d71c81ef5fbaefa81a25273a778e393))
* **migration:** allow shipping method without colon ([#1024](https://github.com/myparcelnl/woocommerce/issues/1024)) ([6c47f56](https://github.com/myparcelnl/woocommerce/commit/6c47f56e5e383f2b87bb36af1f8b1668490f059a))
* **migration:** finish migration even when api key is faulty ([#1023](https://github.com/myparcelnl/woocommerce/issues/1023)) ([7ce87d0](https://github.com/myparcelnl/woocommerce/commit/7ce87d0de16aec18c9fdc36b2df97e0daab90387))
* **migration:** fix allow pickup locations setting not being migrated ([44e29d1](https://github.com/myparcelnl/woocommerce/commit/44e29d14d562280d502a50ec88b3b97d742c7108))
* **migration:** fix existing settings ([e7f6d11](https://github.com/myparcelnl/woocommerce/commit/e7f6d11240e28aec9ca50af2fd192f11f3316369))
* **migration:** fix inverted product settings search query ([e76028e](https://github.com/myparcelnl/woocommerce/commit/e76028eb3948e7a3b2a07595d4344c3a1bb1ee3f))
* **migration:** fix product settings migration failing ([2c32dd7](https://github.com/myparcelnl/woocommerce/commit/2c32dd7011600e097b022ac9508c87dc447770d1))
* **migration:** have weightservice work with all units ([f6e2614](https://github.com/myparcelnl/woocommerce/commit/f6e26144bfc944af5beb6c5d4f652b42c255dcab))
* **migration:** improve migration logic ([5364a02](https://github.com/myparcelnl/woocommerce/commit/5364a0239f284cc5ecaa824a2c47dcf6a5f6430a))
* **migration:** improve migrations ([e340e9d](https://github.com/myparcelnl/woocommerce/commit/e340e9dc8a230d64d93b7278b7c6a49801a51edf))
* **migration:** improve order migration ([bb8e7ef](https://github.com/myparcelnl/woocommerce/commit/bb8e7ef47427c03b05e58589ff3f7bd242adbbe7))
* **migration:** improve pdk migrations ([a970919](https://github.com/myparcelnl/woocommerce/commit/a97091983a40a9bf2354236e9a7fcde03dab33cd))
* **migration:** improve settings migration ([43e0c69](https://github.com/myparcelnl/woocommerce/commit/43e0c697c8b9148a9fa88f45cef3de7be8cbe665))
* **migration:** improve shipping methods migration ([b9383d3](https://github.com/myparcelnl/woocommerce/commit/b9383d34cfa486b100580e1828d6bf987d79900f))
* **migration:** migrate shipments to proper address fields ([f5db010](https://github.com/myparcelnl/woocommerce/commit/f5db0109f7e3cdcdac26a0326f52a1840ba759e9))
* **migration:** prevent removing current version on uninstall ([#1022](https://github.com/myparcelnl/woocommerce/issues/1022)) ([df21c29](https://github.com/myparcelnl/woocommerce/commit/df21c29818627a5c336243f0e81d4ade4169ae10))
* **migration:** set delivery options date to null if it's empty ([#1020](https://github.com/myparcelnl/woocommerce/issues/1020)) ([58fc7a0](https://github.com/myparcelnl/woocommerce/commit/58fc7a002999ddfd6572ba95a97369d300fe35d6))
* **migration:** transform existing carrier settings ([7a9788b](https://github.com/myparcelnl/woocommerce/commit/7a9788b259e3a2b3d224e0b5a8f8772b9da95d51))
* **modal:** fix modal actions ([840f4ad](https://github.com/myparcelnl/woocommerce/commit/840f4adf82dc0828ef7439afb2ced191ac35284d))
* **notes:** get api identifier from order meta data ([91d87b3](https://github.com/myparcelnl/woocommerce/commit/91d87b311a20dabb768ed1ee9023751739eceaae))
* **orders:** always return items array from WcOrderRepository ([#1055](https://github.com/myparcelnl/woocommerce/issues/1055)) ([6fce81c](https://github.com/myparcelnl/woocommerce/commit/6fce81cdce84d3d6bfab78796c0216879e4ac317))
* **orders:** convert weight to grams ([#1021](https://github.com/myparcelnl/woocommerce/issues/1021)) ([bd38290](https://github.com/myparcelnl/woocommerce/commit/bd38290ad041e9e32e6877294a5a872caa1270f3))
* **orders:** do not add return barcodes to order notes ([#998](https://github.com/myparcelnl/woocommerce/issues/998)) ([34ca572](https://github.com/myparcelnl/woocommerce/commit/34ca5727daf9ddba0a1214c385539faf53847ae5))
* **orders:** fix error when getting order via non-object ([0d39b5c](https://github.com/myparcelnl/woocommerce/commit/0d39b5cc0e9b3a301de5962915e673862c657c4a))
* **orders:** fix error when state does not match XX-XX format ([#1038](https://github.com/myparcelnl/woocommerce/issues/1038)) ([1c7023f](https://github.com/myparcelnl/woocommerce/commit/1c7023f72c5581c19811c35d0e8f8b200c0369cd))
* **orders:** fix meta keys being saved multiple times ([98a4a3e](https://github.com/myparcelnl/woocommerce/commit/98a4a3edc6d07389434bbd4f9a02569f3c5799bd))
* **orders:** fix some errors when creating orders ([de47001](https://github.com/myparcelnl/woocommerce/commit/de470019fe2eee1a4277885a262d3a0bfdbf02fe))
* **orders:** fix state and missing address fields errors ([#1019](https://github.com/myparcelnl/woocommerce/issues/1019)) ([8ef3e53](https://github.com/myparcelnl/woocommerce/commit/8ef3e537a19a34928384740b65e7286e6991b286))
* **orders:** fix storing data in hpos ([#1037](https://github.com/myparcelnl/woocommerce/issues/1037)) ([f2e09dd](https://github.com/myparcelnl/woocommerce/commit/f2e09dd0605809191c7894af04a1dec60c5504cc))
* **orders:** fix type error when country is missing ([f016b91](https://github.com/myparcelnl/woocommerce/commit/f016b916c7ed0c467e10473458945bab5056d3ad))
* **orders:** improve order note author mapping ([03e0a75](https://github.com/myparcelnl/woocommerce/commit/03e0a75aa7ed8e7c69f753e89c6a28cf6815e295))
* **permissions:** check all user roles ([#1048](https://github.com/myparcelnl/woocommerce/issues/1048)) ([260ad2e](https://github.com/myparcelnl/woocommerce/commit/260ad2e1453585f033934f3bb8c32f44107d1919))
* **plugin:** fix plugin being deactivated on upgrade ([3be182a](https://github.com/myparcelnl/woocommerce/commit/3be182a04176ac60c2117f0ad4ccdbcb857edfea))
* prefix bulk actions with "MyParcel" ([#999](https://github.com/myparcelnl/woocommerce/issues/999)) ([011376f](https://github.com/myparcelnl/woocommerce/commit/011376f3e95b45f6ace68a4c2b0ebf52e1683b18))
* prevent array to string conversion warning ([90c587d](https://github.com/myparcelnl/woocommerce/commit/90c587da80b39cc2e26420a62ae1386cbe1dda48))
* **product-settings:** fix updating without hpos ([#1040](https://github.com/myparcelnl/woocommerce/issues/1040)) ([e179184](https://github.com/myparcelnl/woocommerce/commit/e179184df07eebcc8ff02d7882438155f213c4dc))
* **product:** fix product settings not working ([756369e](https://github.com/myparcelnl/woocommerce/commit/756369e5848c429c1dc28ff84925a276a87ca02e))
* render settings page ([14e449d](https://github.com/myparcelnl/woocommerce/commit/14e449d69a74c3c29afce6561b189df2d9e9aa63))
* **request:** pass $_COOKIE and $_SERVER to converted requests ([0861ba3](https://github.com/myparcelnl/woocommerce/commit/0861ba36735c4e9d37c7d3269722ad173e11d1a6))
* **requests:** send correct user agent to myparcel api ([12e6afb](https://github.com/myparcelnl/woocommerce/commit/12e6afbb4aafe7d2ec081d2df00d3aaa1437df1f))
* **request:** update guzzle adapter ([02cd681](https://github.com/myparcelnl/woocommerce/commit/02cd681ab544271537dbb9d7e13c07757257ffe2))
* save shipment even when updated is not null ([e1e1849](https://github.com/myparcelnl/woocommerce/commit/e1e1849eb88a4839b170b358ad0345257bd52f92))
* set label description on order ([f7593f3](https://github.com/myparcelnl/woocommerce/commit/f7593f3c0bd7ac3ab23845413f53bcd1f84bb89c))
* set physical properties on pdkorder ([9056140](https://github.com/myparcelnl/woocommerce/commit/9056140712b31fb8fb5735133e3051017318c3d3))
* **settings:** correct keys for webhook settings ([902d7d5](https://github.com/myparcelnl/woocommerce/commit/902d7d52d9c5cb8356969fdbc6e3446f471bf099))
* **settings:** fix correct shipping methods not showing up ([1528585](https://github.com/myparcelnl/woocommerce/commit/1528585be4b0ebd0f1e4683c018b2409b4f7f8d8))
* **settings:** make drop off toggle reactive again ([#1008](https://github.com/myparcelnl/woocommerce/issues/1008)) ([ecb0cf0](https://github.com/myparcelnl/woocommerce/commit/ecb0cf067d57c30ac1e72e6b6c4cc7437adaf018))
* **settings:** prevent array to string conversion notice ([16227b1](https://github.com/myparcelnl/woocommerce/commit/16227b1dcd54b77bd4d5252572fc255a9469b80e))
* show notices through all_admin_notices hook ([613fb73](https://github.com/myparcelnl/woocommerce/commit/613fb731b9abeb935e80f1f0d0a2ddcf27f4a51b))
* update account settings after migration ([c4cffdd](https://github.com/myparcelnl/woocommerce/commit/c4cffdd5693305fc1c8cfdcdf1bf7161dbacf7d4))
* update fields ([1180fdf](https://github.com/myparcelnl/woocommerce/commit/1180fdf17e9f6a64ad144cc4c28eda99f420c07a))
* update frontend routes ([67b5aa1](https://github.com/myparcelnl/woocommerce/commit/67b5aa1acc7105d59c6fa38ed41a418311b68edd))
* update product settings ([f1f2fa3](https://github.com/myparcelnl/woocommerce/commit/f1f2fa32da8648d236198a3a159a379394e77a76))
* use correct option for automatic export ([4cc4959](https://github.com/myparcelnl/woocommerce/commit/4cc4959f63f42d267329a36b96ea20a130ccdf6b))
* **webhooks:** fix webhooks not running ([#1056](https://github.com/myparcelnl/woocommerce/issues/1056)) ([e9cc97c](https://github.com/myparcelnl/woocommerce/commit/e9cc97ce6e382d09bc1862c738879982ee49667b))
* **webhooks:** fix webhooks not working ([#1137](https://github.com/myparcelnl/woocommerce/issues/1137)) ([fcbd7d2](https://github.com/myparcelnl/woocommerce/commit/fcbd7d2cecfcaba7e835e4db17d543e650aaa22e))
* **webhooks:** use json params ([#1046](https://github.com/myparcelnl/woocommerce/issues/1046)) ([0f2f075](https://github.com/myparcelnl/woocommerce/commit/0f2f075feecb4e56af711833cbfe359ca631e18a))
* **webhook:** use correct class to execute webhooks ([#1029](https://github.com/myparcelnl/woocommerce/issues/1029)) ([9cd29ab](https://github.com/myparcelnl/woocommerce/commit/9cd29abe5a17fa78206029f4e2e7ce76ede675c2))
* wrap pdk admin init in window.onload ([899d14d](https://github.com/myparcelnl/woocommerce/commit/899d14d792efc38f88cd4941444b32159b3f48aa))


### :zap: Performance Improvements

* **orders:** fix audits table being queried for each order in overview ([#1156](https://github.com/myparcelnl/woocommerce/issues/1156)) ([dd22902](https://github.com/myparcelnl/woocommerce/commit/dd22902449d65d1e3306cb05b5122d283a7af84e))
* reduce amount of checks for account ([9b83be8](https://github.com/myparcelnl/woocommerce/commit/9b83be81947223718c0f2f96c309b23ca70f7923))


### :sparkles: New Features

* **account:** show delivery options in account ([9d515ad](https://github.com/myparcelnl/woocommerce/commit/9d515ad6d51326adb77f1b0f438fd98d2cb34ee9))
* add bulk actions ([c2ba715](https://github.com/myparcelnl/woocommerce/commit/c2ba715bc6f06caace880b5ab1c983d0a6eb87f3))
* add dhleuroplus and dhlparcelconnect ([643d297](https://github.com/myparcelnl/woocommerce/commit/643d297d94e72ca2eb23c28c4e1fca423063524e))
* add legacy delivery options meta ([#1097](https://github.com/myparcelnl/woocommerce/issues/1097)) ([9545c3a](https://github.com/myparcelnl/woocommerce/commit/9545c3a23e1c1c7e3c8b64aaec865ba8a9f407a3))
* **admin:** add subtext option to field ([5190f08](https://github.com/myparcelnl/woocommerce/commit/5190f08f2b827880317a3f4673290a42fd4edb54))
* **admin:** add text area and code editor component ([5adf95e](https://github.com/myparcelnl/woocommerce/commit/5adf95e290594b7e96af9ddbb656c02cea69f803))
* **admin:** improve button styles ([ba59c53](https://github.com/myparcelnl/woocommerce/commit/ba59c530a1633d5d046ca2a998adf21c03e8a2a4))
* **admin:** improve radio component ([7d1faf3](https://github.com/myparcelnl/woocommerce/commit/7d1faf326af289b2e4c33995d8a6d5d45b9f2003))
* **admin:** make select2 component work properly ([a42cadf](https://github.com/myparcelnl/woocommerce/commit/a42cadf5c523b690ba1b7f5a61ae36bfcb66fa95))
* **admin:** use native pdk product settings view ([da3b936](https://github.com/myparcelnl/woocommerce/commit/da3b93682cd35365a39cb3495b8753f682e82ff5))
* allow configuration based on shipping classes ([#1145](https://github.com/myparcelnl/woocommerce/issues/1145)) ([ff282a1](https://github.com/myparcelnl/woocommerce/commit/ff282a19ac551e24c7eca84228ac24d4933d5f0b))
* allow picking package type per shipping method ([#1104](https://github.com/myparcelnl/woocommerce/issues/1104)) ([3cd31f6](https://github.com/myparcelnl/woocommerce/commit/3cd31f621c6f41d39db8b7a4880907222553737e))
* allow shop manager to operate plugin ([#1028](https://github.com/myparcelnl/woocommerce/issues/1028)) ([be28524](https://github.com/myparcelnl/woocommerce/commit/be28524eaaae66650afb9e08b88ab2ba47012b9f))
* **checkout:** add blocks compatibility ([#1065](https://github.com/myparcelnl/woocommerce/issues/1065)) ([7b69aaa](https://github.com/myparcelnl/woocommerce/commit/7b69aaa027f49bdfe30d952e50e6aaafd8a60969))
* **checkout:** improve checkout logic ([cdfd72a](https://github.com/myparcelnl/woocommerce/commit/cdfd72a7a0dbcdc4aaf0ad86e0e7279e992d208c))
* **checkout:** include address line 2 in full street ([#1032](https://github.com/myparcelnl/woocommerce/issues/1032)) ([31aa6f2](https://github.com/myparcelnl/woocommerce/commit/31aa6f20654aed17113e376c32db8cd1bbdf7415))
* **checkout:** upgrade to delivery options v6.x ([9c23b4f](https://github.com/myparcelnl/woocommerce/commit/9c23b4f630ff4dbccb42c5bcd5ea64a3f9236aea))
* **deps:** update [@myparcel-pdk](https://github.com/myparcel-pdk) packages ([82986b9](https://github.com/myparcelnl/woocommerce/commit/82986b9884cea8d2d065ccec77e48e1dfd883e66))
* **deps:** update @myparcel-pdk/admin ([4b19ca2](https://github.com/myparcelnl/woocommerce/commit/4b19ca22e9f9c34463a7ab8fa8bac4ae7e6a5d22))
* **deps:** update myparcelnl/pdk from 2.4.1 to 2.5.2 ([f09fabb](https://github.com/myparcelnl/woocommerce/commit/f09fabb28c8c22dbef66764367ada481d1fe3722))
* **deps:** update myparcelnl/pdk to from 2.3.0 to 2.4.1 ([a9e294e](https://github.com/myparcelnl/woocommerce/commit/a9e294e9393d392919aba31d765b4e943ce15ae5)), closes [#98](https://github.com/myparcelnl/woocommerce/issues/98) [#105](https://github.com/myparcelnl/woocommerce/issues/105)
* **deps:** update myparcelnl/pdk to v2.6.2 ([c9d367f](https://github.com/myparcelnl/woocommerce/commit/c9d367f08a6fee378abc1a32045eb8c6398a4edc))
* **deps:** upgrade @myparcel-pdk/* ([2eafb96](https://github.com/myparcelnl/woocommerce/commit/2eafb961ef40e9687280dce8d0306cc930fca5ff))
* **deps:** upgrade @myparcel-pdk/* ([dcb2153](https://github.com/myparcelnl/woocommerce/commit/dcb21530bc70df86561b67047c8b7add747cfcbb))
* **deps:** upgrade @myparcel-pdk/* ([1271aac](https://github.com/myparcelnl/woocommerce/commit/1271aacc091f9b356ba7acb414acdffd2b5ebab0))
* **deps:** upgrade @myparcel-pdk/* ([f30713f](https://github.com/myparcelnl/woocommerce/commit/f30713f92274d98c85c9de0ccc9822a44048997d))
* **deps:** upgrade @myparcel-pdk/* ([324a24e](https://github.com/myparcelnl/woocommerce/commit/324a24ebfae8f2507967669d849f3e025c6e6ce1))
* **deps:** upgrade @myparcel-pdk/* ([813bfb0](https://github.com/myparcelnl/woocommerce/commit/813bfb07d5c1841d26b0fc97d08746cbe90e2f3a))
* **deps:** upgrade @myparcel-pdk/* ([d673174](https://github.com/myparcelnl/woocommerce/commit/d673174f722d0e27e7c1dfc02fc8f004038aee8f))
* **deps:** upgrade @myparcel-pdk/* ([0717e2d](https://github.com/myparcelnl/woocommerce/commit/0717e2d2db431f00bc63da3f8f929ecf697a328e))
* **deps:** upgrade @myparcel-pdk/* ([012f0dc](https://github.com/myparcelnl/woocommerce/commit/012f0dcf8118349e2ef95e34661652edecc0c19d))
* **deps:** upgrade myparcelnl/pdk to v2.19.0 ([a305f11](https://github.com/myparcelnl/woocommerce/commit/a305f114a1f47e892562c654de2d057b4717c2e1))
* **deps:** upgrade myparcelnl/pdk to v2.25.2 ([42b3fb0](https://github.com/myparcelnl/woocommerce/commit/42b3fb092a6b9ec449ac0baccb5f8f991f1e8092))
* **deps:** upgrade myparcelnl/pdk to v2.28.0 ([3d944b3](https://github.com/myparcelnl/woocommerce/commit/3d944b375dddd7419395f6d2e7f3e49e67221cc3))
* **deps:** upgrade myparcelnl/pdk to v2.28.2 ([e3a7c47](https://github.com/myparcelnl/woocommerce/commit/e3a7c47ded24e26ea025b1faa1074661afeb706b))
* **deps:** upgrade myparcelnl/pdk to v2.31.3 ([083be23](https://github.com/myparcelnl/woocommerce/commit/083be23de8f9ad3f452cb62b549bfbeff84e665c))
* **deps:** upgrade myparcelnl/pdk to v2.34.0 ([e5f9a0a](https://github.com/myparcelnl/woocommerce/commit/e5f9a0a41eed179f239e925c5fe8c0d9856f4d56))
* **deps:** upgrade myparcelnl/pdk to v2.36.1 ([7a32728](https://github.com/myparcelnl/woocommerce/commit/7a32728aaf1d615ed046d931b9e62123332bfc32))
* **deps:** upgrade myparcelnl/pdk to v2.36.3 ([36ecd12](https://github.com/myparcelnl/woocommerce/commit/36ecd1249e604d1dc7a74e7a83d0f8cad98a082f))
* **deps:** upgrade myparcelnl/pdk to v2.39.2 ([663a31b](https://github.com/myparcelnl/woocommerce/commit/663a31b72a723206ea4ffbef2c5b7bf3090bfa7f))
* **deps:** upgrade myparcelnl/pdk to v2.42.0 ([c1b1220](https://github.com/myparcelnl/woocommerce/commit/c1b1220c1a5069e8e06387c240cf16b694b274f3))
* **deps:** upgrade myparcelnl/pdk to v2.43.2 ([d011b93](https://github.com/myparcelnl/woocommerce/commit/d011b9303ddc266ef87d36a27642bb1e23210546))
* **deps:** upgrade myparcelnl/pdk to v2.45.0 ([1569918](https://github.com/myparcelnl/woocommerce/commit/1569918b54ac4bf676640e9995933708d1447fb8))
* **deps:** upgrade myparcelnl/pdk to v2.8.0 ([0c6385e](https://github.com/myparcelnl/woocommerce/commit/0c6385e90c692f8ea81fd243339b2368c629d040))
* **dhl:** add vat and eori fields for europlus ([#974](https://github.com/myparcelnl/woocommerce/issues/974)) ([0517dac](https://github.com/myparcelnl/woocommerce/commit/0517dacdb9492f0e3289968f171179fd1b96b2b0))
* **endpoints:** improve endpoint logic ([6e24a68](https://github.com/myparcelnl/woocommerce/commit/6e24a68d783c614876894446ec0a3092d8d69e3c))
* improve automatic export ([#1053](https://github.com/myparcelnl/woocommerce/issues/1053)) ([40e239f](https://github.com/myparcelnl/woocommerce/commit/40e239fb65f3a3e6a2a3f9b2a35b04ba6efcfec0))
* **migration:** add package type to product settings ([#1006](https://github.com/myparcelnl/woocommerce/issues/1006)) ([847552a](https://github.com/myparcelnl/woocommerce/commit/847552a3df16a4aa6ad7a588764ced2afa8824c9))
* move frontend checkout logic to pdk ([d64e053](https://github.com/myparcelnl/woocommerce/commit/d64e053d8aea0bfeee23f4dd9d9756d5e5b2e68f))
* move showing/hiding logic to app config ([4fc8a25](https://github.com/myparcelnl/woocommerce/commit/4fc8a25c8d23beff879d9c3e65134fe07477ea10))
* **notes:** add hook to post note after adding to order ([0588f17](https://github.com/myparcelnl/woocommerce/commit/0588f178a605f9dc27ba3ff8123a60cdd93c6531))
* **order:** add reference identifier ([#1033](https://github.com/myparcelnl/woocommerce/issues/1033)) ([a8df194](https://github.com/myparcelnl/woocommerce/commit/a8df194c8319c93a32fb7a0517cd626b9dd007ec))
* **order:** get order notes ([a009cf7](https://github.com/myparcelnl/woocommerce/commit/a009cf7abbbcff47d5977507c9070f8da3945789))
* **orders:** change order status on label print ([#1016](https://github.com/myparcelnl/woocommerce/issues/1016)) ([fa5f5b6](https://github.com/myparcelnl/woocommerce/commit/fa5f5b6b234be9588a2bb23c1a4b0f383933a86b))
* **order:** support digital stamp weight range option ([#1035](https://github.com/myparcelnl/woocommerce/issues/1035)) ([9750ff4](https://github.com/myparcelnl/woocommerce/commit/9750ff48fc7e19bb01a6f50972b11480bfbcf57d))
* **plugin:** add links to plugin meta ([7065195](https://github.com/myparcelnl/woocommerce/commit/70651959ddca19bb4ada83b6790fb9d16f18bcbb))
* prepend order note with text from settings ([68f6fe2](https://github.com/myparcelnl/woocommerce/commit/68f6fe22b2f9d2b70b1f2f6fd2cde5b0b48880ac))
* **product:** support variation product settings ([#996](https://github.com/myparcelnl/woocommerce/issues/996)) ([d4ae5b3](https://github.com/myparcelnl/woocommerce/commit/d4ae5b382ab0aa15083b96303abf536e4f7abf5d))
* rebuild entire plugin with pdk ([bbab1af](https://github.com/myparcelnl/woocommerce/commit/bbab1af1309c6611a9e0c269687377b0a6d81d5e))
* retrieve shipping methods ([cd5f5d3](https://github.com/myparcelnl/woocommerce/commit/cd5f5d3e588dd07fad99d96f0b8c86922a878510))
* **settings:** improve settings views ([106f09c](https://github.com/myparcelnl/woocommerce/commit/106f09c8c7d42246b8f45cf2c8455d7fc46f99b7))
* **shipments:** add barcode as order note ([#1004](https://github.com/myparcelnl/woocommerce/issues/1004)) ([7155cb8](https://github.com/myparcelnl/woocommerce/commit/7155cb82a980bca7f02dfa88ecf4eaa541126273))
* support woocommerce hpos ([74c9ccf](https://github.com/myparcelnl/woocommerce/commit/74c9ccf0aaf2da9f1bb037a163d946ff791304c6))
* **translations:** migrate to pdk builder translation logic ([542f25c](https://github.com/myparcelnl/woocommerce/commit/542f25cc023b54e9fb0f055ddbd24680e52c5a37))
* update dependencies ([d13568d](https://github.com/myparcelnl/woocommerce/commit/d13568df051fcaf022c76dfc84068f21ce90ca80))
* update to latest delivery options ([#1085](https://github.com/myparcelnl/woocommerce/issues/1085)) ([69b24b9](https://github.com/myparcelnl/woocommerce/commit/69b24b998700973f8badbc033cb75917a8d595dc))

## 🚧 This version is not ready for production use 🚧

This is the beta release of MyParcel for WooCommerce v5.x. We've rewritten the entire plugin from scratch, using the [MyParcel Plugin Development Kit].

For a less bug-prone experience, we recommend you use the stable or release candidate versions of the plugin instead. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-beta.x form] or by sending an email to [support@myparcel.nl]. Use it in production at your own risk.

[Bug report for v5.0.0-beta.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=beta&template=ZZ-BUG-REPORT-v5.yml
[MyParcel Plugin Development Kit]: https://developer.myparcel.nl/documentation/52.pdk/
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-beta.11](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-beta.10...v5.0.0-beta.11) (2024-09-20)


### :bug: Bug Fixes

* **webhooks:** fix webhooks not working ([#1137](https://github.com/myparcelnl/woocommerce/issues/1137)) ([fcbd7d2](https://github.com/myparcelnl/woocommerce/commit/fcbd7d2cecfcaba7e835e4db17d543e650aaa22e))


### :zap: Performance Improvements

* **orders:** fix audits table being queried for each order in overview ([#1156](https://github.com/myparcelnl/woocommerce/issues/1156)) ([dd22902](https://github.com/myparcelnl/woocommerce/commit/dd22902449d65d1e3306cb05b5122d283a7af84e))


### :sparkles: New Features

* **deps:** upgrade @myparcel-pdk/* ([dcb2153](https://github.com/myparcelnl/woocommerce/commit/dcb21530bc70df86561b67047c8b7add747cfcbb))
* **deps:** upgrade myparcelnl/pdk to v2.43.2 ([d011b93](https://github.com/myparcelnl/woocommerce/commit/d011b9303ddc266ef87d36a27642bb1e23210546))

## 🚧 This version is not ready for production use 🚧

This is the beta release of MyParcel for WooCommerce v5.x. We've rewritten the entire plugin from scratch, using the [MyParcel Plugin Development Kit].

For a less bug-prone experience, we recommend you use the stable or release candidate versions of the plugin instead. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-beta.x form] or by sending an email to [support@myparcel.nl]. Use it in production at your own risk.

[Bug report for v5.0.0-beta.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=beta&template=ZZ-BUG-REPORT-v5.yml
[MyParcel Plugin Development Kit]: https://developer.myparcel.nl/documentation/52.pdk/
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-beta.10](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-beta.9...v5.0.0-beta.10) (2024-08-07)


### :bug: Bug Fixes

* **checkout:** use show delivery options on backorders setting ([#1136](https://github.com/myparcelnl/woocommerce/issues/1136)) ([9495554](https://github.com/myparcelnl/woocommerce/commit/94955541e71bb4a7ad479fef0d3955d18a722fbd))


### :sparkles: New Features

* **deps:** upgrade myparcelnl/pdk to v2.42.0 ([c1b1220](https://github.com/myparcelnl/woocommerce/commit/c1b1220c1a5069e8e06387c240cf16b694b274f3))

## 🚧 This version is not ready for production use 🚧

This is the beta release of MyParcel WooCommerce v5.x. We've rewritten the entire plugin from scratch, using the [MyParcel Plugin Development Kit].

For a less bug-prone experience, we recommend you use the stable or release candidate versions of the plugin instead. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-beta.x form] or by sending an email to [support@myparcel.nl]. Use it in production at your own risk.

[Bug report for v5.0.0-beta.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=beta&template=ZZ-BUG-REPORT-v5.yml
[MyParcel Plugin Development Kit]: https://developer.myparcel.nl/documentation/52.pdk/
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-beta.9](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-beta.8...v5.0.0-beta.9) (2024-07-12)


### :sparkles: New Features

* add legacy delivery options meta ([#1097](https://github.com/myparcelnl/woocommerce/issues/1097)) ([9545c3a](https://github.com/myparcelnl/woocommerce/commit/9545c3a23e1c1c7e3c8b64aaec865ba8a9f407a3))
* allow picking package type per shipping method ([#1104](https://github.com/myparcelnl/woocommerce/issues/1104)) ([3cd31f6](https://github.com/myparcelnl/woocommerce/commit/3cd31f621c6f41d39db8b7a4880907222553737e))
* **deps:** upgrade @myparcel-pdk/* ([1271aac](https://github.com/myparcelnl/woocommerce/commit/1271aacc091f9b356ba7acb414acdffd2b5ebab0))
* **deps:** upgrade myparcelnl/pdk to v2.39.2 ([663a31b](https://github.com/myparcelnl/woocommerce/commit/663a31b72a723206ea4ffbef2c5b7bf3090bfa7f))


### :bug: Bug Fixes

* add default value for delivery options position filter ([97600e6](https://github.com/myparcelnl/woocommerce/commit/97600e64437f72a26a43904f9bf655bad5cdf3c7))
* **admin:** fix button dropdowns not expanding correctly ([#1129](https://github.com/myparcelnl/woocommerce/issues/1129)) ([6445513](https://github.com/myparcelnl/woocommerce/commit/6445513167d260cf3ee58556b13f4124b305b297))
* **admin:** fix dropoff input not showing ([#1110](https://github.com/myparcelnl/woocommerce/issues/1110)) ([e2da027](https://github.com/myparcelnl/woocommerce/commit/e2da027c917ddef5b4a7b9f5421bdd20db9ea8c5))

## 🚧 This version is not ready for production use 🚧

This is the beta release of MyParcel WooCommerce v5.x. We've rewritten the entire plugin from scratch, using the [MyParcel Plugin Development Kit].

For a less bug-prone experience, we recommend you use the stable or release candidate versions of the plugin instead. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-beta.x form] or by sending an email to [support@myparcel.nl]. Use it in production at your own risk.

[Bug report for v5.0.0-beta.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=beta&template=ZZ-BUG-REPORT-v5.yml
[MyParcel Plugin Development Kit]: https://developer.myparcel.nl/documentation/52.pdk/
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-beta.8](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-beta.7...v5.0.0-beta.8) (2024-05-22)


### :sparkles: New Features

* **deps:** upgrade myparcelnl/pdk to v2.36.3 ([36ecd12](https://github.com/myparcelnl/woocommerce/commit/36ecd1249e604d1dc7a74e7a83d0f8cad98a082f))

## 🚧 This version is not ready for production use 🚧

This is the beta release of MyParcel WooCommerce v5.x. We've rewritten the entire plugin from scratch, using the [MyParcel Plugin Development Kit].

For a less bug-prone experience, we recommend you use the stable or release candidate versions of the plugin instead. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-beta.x form] or by sending an email to [support@myparcel.nl]. Use it in production at your own risk.

[Bug report for v5.0.0-beta.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=beta&template=ZZ-BUG-REPORT-v5.yml
[MyParcel Plugin Development Kit]: https://developer.myparcel.nl/documentation/52.pdk/
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-beta.7](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-beta.6...v5.0.0-beta.7) (2024-05-14)


### :bug: Bug Fixes

* **checkout:** have delivery options show in classic checkout ([#1094](https://github.com/myparcelnl/woocommerce/issues/1094)) ([4812cca](https://github.com/myparcelnl/woocommerce/commit/4812cca4d76fcedb103fe2088327c856336af188))

## 🚧 This version is not ready for production use 🚧

This is the beta release of MyParcel WooCommerce v5.x. We've rewritten the entire plugin from scratch, using the [MyParcel Plugin Development Kit].

For a less bug-prone experience, we recommend you use the stable or release candidate versions of the plugin instead. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-beta.x form] or by sending an email to [support@myparcel.nl]. Use it in production at your own risk.

[Bug report for v5.0.0-beta.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=beta&template=ZZ-BUG-REPORT-v5.yml
[MyParcel Plugin Development Kit]: https://developer.myparcel.nl/documentation/52.pdk/
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-beta.6](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-beta.5...v5.0.0-beta.6) (2024-04-25)


### :sparkles: New Features

* **deps:** upgrade @myparcel-pdk/* ([f30713f](https://github.com/myparcelnl/woocommerce/commit/f30713f92274d98c85c9de0ccc9822a44048997d))
* **deps:** upgrade myparcelnl/pdk to v2.36.1 ([7a32728](https://github.com/myparcelnl/woocommerce/commit/7a32728aaf1d615ed046d931b9e62123332bfc32))
* update to latest delivery options ([#1085](https://github.com/myparcelnl/woocommerce/issues/1085)) ([69b24b9](https://github.com/myparcelnl/woocommerce/commit/69b24b998700973f8badbc033cb75917a8d595dc))

## 🚧 This version is not ready for production use 🚧

This is the beta release of MyParcel WooCommerce v5.x. We've rewritten the entire plugin from scratch, using the [MyParcel Plugin Development Kit].

For a less bug-prone experience, we recommend you use the stable or release candidate versions of the plugin instead. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-beta.x form] or by sending an email to [support@myparcel.nl]. Use it in production at your own risk.

[Bug report for v5.0.0-beta.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=beta&template=ZZ-BUG-REPORT-v5.yml
[MyParcel Plugin Development Kit]: https://developer.myparcel.nl/documentation/52.pdk/
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-beta.5](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-beta.4...v5.0.0-beta.5) (2024-03-27)


### :bug: Bug Fixes

* create audits table on installation ([#1068](https://github.com/myparcelnl/woocommerce/issues/1068)) ([7cc0b25](https://github.com/myparcelnl/woocommerce/commit/7cc0b25800c41f4d8e549648119d32c7503c4983))


### :sparkles: New Features

* **checkout:** add blocks compatibility ([#1065](https://github.com/myparcelnl/woocommerce/issues/1065)) ([7b69aaa](https://github.com/myparcelnl/woocommerce/commit/7b69aaa027f49bdfe30d952e50e6aaafd8a60969))
* **checkout:** upgrade to delivery options v6.x ([9c23b4f](https://github.com/myparcelnl/woocommerce/commit/9c23b4f630ff4dbccb42c5bcd5ea64a3f9236aea))
* **deps:** upgrade @myparcel-pdk/* ([324a24e](https://github.com/myparcelnl/woocommerce/commit/324a24ebfae8f2507967669d849f3e025c6e6ce1))
* **deps:** upgrade myparcelnl/pdk to v2.34.0 ([e5f9a0a](https://github.com/myparcelnl/woocommerce/commit/e5f9a0a41eed179f239e925c5fe8c0d9856f4d56))

## 🚧 This version is not ready for production use 🚧

This is the beta release of MyParcel WooCommerce v5.x. We've rewritten the entire plugin from scratch, using the [MyParcel Plugin Development Kit].

For a less bug-prone experience, we recommend you use the stable or release candidate versions of the plugin instead. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-beta.x form] or by sending an email to [support@myparcel.nl]. Use it in production at your own risk.

[Bug report for v5.0.0-beta.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=beta&template=ZZ-BUG-REPORT-v5.yml
[MyParcel Plugin Development Kit]: https://developer.myparcel.nl/documentation/52.pdk/
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-beta.4](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-beta.3...v5.0.0-beta.4) (2023-11-30)


### :bug: Bug Fixes

* **cronservice:** make callable usable ([#1052](https://github.com/myparcelnl/woocommerce/issues/1052)) ([11882b0](https://github.com/myparcelnl/woocommerce/commit/11882b067287d51c37f163b99cf29a56c7501a11))
* fix myparcelbe version not working ([c9eaba5](https://github.com/myparcelnl/woocommerce/commit/c9eaba57dacb499bde695f9bcd21f3e4a85b1c23))
* **orders:** always return items array from WcOrderRepository ([#1055](https://github.com/myparcelnl/woocommerce/issues/1055)) ([6fce81c](https://github.com/myparcelnl/woocommerce/commit/6fce81cdce84d3d6bfab78796c0216879e4ac317))
* **permissions:** check all user roles ([#1048](https://github.com/myparcelnl/woocommerce/issues/1048)) ([260ad2e](https://github.com/myparcelnl/woocommerce/commit/260ad2e1453585f033934f3bb8c32f44107d1919))
* **webhooks:** fix webhooks not running ([#1056](https://github.com/myparcelnl/woocommerce/issues/1056)) ([e9cc97c](https://github.com/myparcelnl/woocommerce/commit/e9cc97ce6e382d09bc1862c738879982ee49667b))
* **webhooks:** use json params ([#1046](https://github.com/myparcelnl/woocommerce/issues/1046)) ([0f2f075](https://github.com/myparcelnl/woocommerce/commit/0f2f075feecb4e56af711833cbfe359ca631e18a))


### :sparkles: New Features

* **deps:** upgrade @myparcel-pdk/* ([813bfb0](https://github.com/myparcelnl/woocommerce/commit/813bfb07d5c1841d26b0fc97d08746cbe90e2f3a))
* **deps:** upgrade myparcelnl/pdk to v2.31.3 ([083be23](https://github.com/myparcelnl/woocommerce/commit/083be23de8f9ad3f452cb62b549bfbeff84e665c))
* improve automatic export ([#1053](https://github.com/myparcelnl/woocommerce/issues/1053)) ([40e239f](https://github.com/myparcelnl/woocommerce/commit/40e239fb65f3a3e6a2a3f9b2a35b04ba6efcfec0))

## 🚧 This version is not ready for production use 🚧

This is the beta release of MyParcel WooCommerce v5.x. We've rewritten the entire plugin from scratch, using the [MyParcel Plugin Development Kit].

For a less bug-prone experience, we recommend you use the stable or release candidate versions of the plugin instead. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-beta.x form] or by sending an email to [support@myparcel.nl]. Use it in production at your own risk.

[Bug report for v5.0.0-beta.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=beta&template=ZZ-BUG-REPORT-v5.yml
[MyParcel Plugin Development Kit]: https://developer.myparcel.nl/documentation/52.pdk/
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-beta.3](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-beta.2...v5.0.0-beta.3) (2023-10-24)


### :bug: Bug Fixes

* **orders:** fix error when state does not match XX-XX format ([#1038](https://github.com/myparcelnl/woocommerce/issues/1038)) ([1c7023f](https://github.com/myparcelnl/woocommerce/commit/1c7023f72c5581c19811c35d0e8f8b200c0369cd))
* **product-settings:** fix updating without hpos ([#1040](https://github.com/myparcelnl/woocommerce/issues/1040)) ([e179184](https://github.com/myparcelnl/woocommerce/commit/e179184df07eebcc8ff02d7882438155f213c4dc))


### :sparkles: New Features

* **deps:** upgrade @myparcel-pdk/* ([d673174](https://github.com/myparcelnl/woocommerce/commit/d673174f722d0e27e7c1dfc02fc8f004038aee8f))
* **deps:** upgrade myparcelnl/pdk to v2.28.2 ([e3a7c47](https://github.com/myparcelnl/woocommerce/commit/e3a7c47ded24e26ea025b1faa1074661afeb706b))

## 🚧 This version is not ready for production use 🚧

This is the beta release of MyParcel WooCommerce v5.x. We've rewritten the entire plugin from scratch, using the [MyParcel Plugin Development Kit].

For a less bug-prone experience, we recommend you use the stable or release candidate versions of the plugin instead. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-beta.x form] or by sending an email to [support@myparcel.nl]. Use it in production at your own risk.

[Bug report for v5.0.0-beta.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=beta&template=ZZ-BUG-REPORT-v5.yml
[MyParcel Plugin Development Kit]: https://developer.myparcel.nl/documentation/52.pdk/
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-beta.2](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-beta.1...v5.0.0-beta.2) (2023-10-16)


### :bug: Bug Fixes

* **admin:** show correct styling in settings page ([#1036](https://github.com/myparcelnl/woocommerce/issues/1036)) ([dbb4be1](https://github.com/myparcelnl/woocommerce/commit/dbb4be15ca6e39f62c0574f625204bb4425928fe))
* **compatibility:** reduce conflicts with other plugins ([#1025](https://github.com/myparcelnl/woocommerce/issues/1025)) ([4b90766](https://github.com/myparcelnl/woocommerce/commit/4b907665e3bb4055c892bfc590a2d27a9f82303f))
* **export:** fix multiple automatic exports ([#1034](https://github.com/myparcelnl/woocommerce/issues/1034)) ([bc610ad](https://github.com/myparcelnl/woocommerce/commit/bc610adf8e9a05be800b4e9c53da43f1951f5352))
* fix fatal error caused by wrongly prefixed file ([96fe2b7](https://github.com/myparcelnl/woocommerce/commit/96fe2b7eb4054b691c3edd3f50e75c84b839d291))
* fix ts imports ([#1030](https://github.com/myparcelnl/woocommerce/issues/1030)) ([c6c2afc](https://github.com/myparcelnl/woocommerce/commit/c6c2afc2aef1b8f4b25bd3e3724ab597c1ee706d))
* **migration:** allow shipping method without colon ([#1024](https://github.com/myparcelnl/woocommerce/issues/1024)) ([6c47f56](https://github.com/myparcelnl/woocommerce/commit/6c47f56e5e383f2b87bb36af1f8b1668490f059a))
* **migration:** finish migration even when api key is faulty ([#1023](https://github.com/myparcelnl/woocommerce/issues/1023)) ([7ce87d0](https://github.com/myparcelnl/woocommerce/commit/7ce87d0de16aec18c9fdc36b2df97e0daab90387))
* **migration:** prevent removing current version on uninstall ([#1022](https://github.com/myparcelnl/woocommerce/issues/1022)) ([df21c29](https://github.com/myparcelnl/woocommerce/commit/df21c29818627a5c336243f0e81d4ade4169ae10))
* **orders:** fix storing data in hpos ([#1037](https://github.com/myparcelnl/woocommerce/issues/1037)) ([f2e09dd](https://github.com/myparcelnl/woocommerce/commit/f2e09dd0605809191c7894af04a1dec60c5504cc))
* **webhook:** use correct class to execute webhooks ([#1029](https://github.com/myparcelnl/woocommerce/issues/1029)) ([9cd29ab](https://github.com/myparcelnl/woocommerce/commit/9cd29abe5a17fa78206029f4e2e7ce76ede675c2))


### :sparkles: New Features

* allow shop manager to operate plugin ([#1028](https://github.com/myparcelnl/woocommerce/issues/1028)) ([be28524](https://github.com/myparcelnl/woocommerce/commit/be28524eaaae66650afb9e08b88ab2ba47012b9f))
* **checkout:** include address line 2 in full street ([#1032](https://github.com/myparcelnl/woocommerce/issues/1032)) ([31aa6f2](https://github.com/myparcelnl/woocommerce/commit/31aa6f20654aed17113e376c32db8cd1bbdf7415))
* **deps:** upgrade @myparcel-pdk/* ([0717e2d](https://github.com/myparcelnl/woocommerce/commit/0717e2d2db431f00bc63da3f8f929ecf697a328e))
* **deps:** upgrade myparcelnl/pdk to v2.28.0 ([3d944b3](https://github.com/myparcelnl/woocommerce/commit/3d944b375dddd7419395f6d2e7f3e49e67221cc3))
* **order:** add reference identifier ([#1033](https://github.com/myparcelnl/woocommerce/issues/1033)) ([a8df194](https://github.com/myparcelnl/woocommerce/commit/a8df194c8319c93a32fb7a0517cd626b9dd007ec))
* **order:** support digital stamp weight range option ([#1035](https://github.com/myparcelnl/woocommerce/issues/1035)) ([9750ff4](https://github.com/myparcelnl/woocommerce/commit/9750ff48fc7e19bb01a6f50972b11480bfbcf57d))

## 🚧 This version is not ready for production use 🚧

This is the beta release of MyParcel WooCommerce v5.x. We've rewritten the entire plugin from scratch, using the [MyParcel Plugin Development Kit].

For a less bug-prone experience, we recommend you use the stable or release candidate versions of the plugin instead. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-beta.x form] or by sending an email to [support@myparcel.nl]. Use it in production at your own risk.

[Bug report for v5.0.0-beta.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=beta&template=ZZ-BUG-REPORT-v5.yml
[MyParcel Plugin Development Kit]: https://developer.myparcel.nl/documentation/52.pdk/
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-beta.2](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-beta.1...v5.0.0-beta.2) (2023-10-16)


### :bug: Bug Fixes

* **admin:** show correct styling in settings page ([#1036](https://github.com/myparcelnl/woocommerce/issues/1036)) ([dbb4be1](https://github.com/myparcelnl/woocommerce/commit/dbb4be15ca6e39f62c0574f625204bb4425928fe))
* **compatibility:** reduce conflicts with other plugins ([#1025](https://github.com/myparcelnl/woocommerce/issues/1025)) ([4b90766](https://github.com/myparcelnl/woocommerce/commit/4b907665e3bb4055c892bfc590a2d27a9f82303f))
* **export:** fix multiple automatic exports ([#1034](https://github.com/myparcelnl/woocommerce/issues/1034)) ([bc610ad](https://github.com/myparcelnl/woocommerce/commit/bc610adf8e9a05be800b4e9c53da43f1951f5352))
* fix ts imports ([#1030](https://github.com/myparcelnl/woocommerce/issues/1030)) ([c6c2afc](https://github.com/myparcelnl/woocommerce/commit/c6c2afc2aef1b8f4b25bd3e3724ab597c1ee706d))
* **migration:** allow shipping method without colon ([#1024](https://github.com/myparcelnl/woocommerce/issues/1024)) ([6c47f56](https://github.com/myparcelnl/woocommerce/commit/6c47f56e5e383f2b87bb36af1f8b1668490f059a))
* **migration:** finish migration even when api key is faulty ([#1023](https://github.com/myparcelnl/woocommerce/issues/1023)) ([7ce87d0](https://github.com/myparcelnl/woocommerce/commit/7ce87d0de16aec18c9fdc36b2df97e0daab90387))
* **migration:** prevent removing current version on uninstall ([#1022](https://github.com/myparcelnl/woocommerce/issues/1022)) ([df21c29](https://github.com/myparcelnl/woocommerce/commit/df21c29818627a5c336243f0e81d4ade4169ae10))
* **orders:** fix storing data in hpos ([#1037](https://github.com/myparcelnl/woocommerce/issues/1037)) ([f2e09dd](https://github.com/myparcelnl/woocommerce/commit/f2e09dd0605809191c7894af04a1dec60c5504cc))
* **webhook:** use correct class to execute webhooks ([#1029](https://github.com/myparcelnl/woocommerce/issues/1029)) ([9cd29ab](https://github.com/myparcelnl/woocommerce/commit/9cd29abe5a17fa78206029f4e2e7ce76ede675c2))


### :sparkles: New Features

* **checkout:** include address line 2 in full street ([#1032](https://github.com/myparcelnl/woocommerce/issues/1032)) ([31aa6f2](https://github.com/myparcelnl/woocommerce/commit/31aa6f20654aed17113e376c32db8cd1bbdf7415))
* **deps:** upgrade @myparcel-pdk/* ([0717e2d](https://github.com/myparcelnl/woocommerce/commit/0717e2d2db431f00bc63da3f8f929ecf697a328e))
* **deps:** upgrade myparcelnl/pdk to v2.28.0 ([3d944b3](https://github.com/myparcelnl/woocommerce/commit/3d944b375dddd7419395f6d2e7f3e49e67221cc3))
* **order:** add reference identifier ([#1033](https://github.com/myparcelnl/woocommerce/issues/1033)) ([a8df194](https://github.com/myparcelnl/woocommerce/commit/a8df194c8319c93a32fb7a0517cd626b9dd007ec))
* **order:** support digital stamp weight range option ([#1035](https://github.com/myparcelnl/woocommerce/issues/1035)) ([9750ff4](https://github.com/myparcelnl/woocommerce/commit/9750ff48fc7e19bb01a6f50972b11480bfbcf57d))

## 🚧 This version is not ready for production use 🚧

This is the beta release of MyParcel WooCommerce v5.x. We've rewritten the entire plugin from scratch, using the [MyParcel Plugin Development Kit].

For a less bug-prone experience, we recommend you use the stable or release candidate versions of the plugin instead. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-beta.x form] or by sending an email to [support@myparcel.nl]. Use it in production at your own risk.

[Bug report for v5.0.0-beta.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=beta&template=ZZ-BUG-REPORT-v5.yml
[MyParcel Plugin Development Kit]: https://developer.myparcel.nl/documentation/52.pdk/
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-beta.1](https://github.com/myparcelnl/woocommerce/compare/v4.19.1...v5.0.0-beta.1) (2023-09-29)


### ⚠ BREAKING CHANGES

* rebuild entire plugin with pdk

### :zap: Performance Improvements

* reduce amount of checks for account ([9b83be8](https://github.com/myparcelnl/woocommerce/commit/9b83be81947223718c0f2f96c309b23ca70f7923))


### :bug: Bug Fixes

* add missing entry to container ([f4cb71b](https://github.com/myparcelnl/woocommerce/commit/f4cb71bb689836a1bd956b4d55658f521f0716ad))
* **admin:** add missing animations ([132da45](https://github.com/myparcelnl/woocommerce/commit/132da45d6ee2a2f1d2156b1ae4132aa23829457d))
* **admin:** add multi select input ([20e71a5](https://github.com/myparcelnl/woocommerce/commit/20e71a5c0d021ac8df30c775fe5bb564688a31d3))
* **admin:** change shipment label row transition ([c2d7cad](https://github.com/myparcelnl/woocommerce/commit/c2d7cad9027608c835c5cec2c1b7cb9942a5168d))
* **admin:** do not render untranslated subtext ([fb0ccce](https://github.com/myparcelnl/woocommerce/commit/fb0ccce444cee85a7ca12a22f89f1b9e90a7207f))
* **admin:** fix code editor not being used ([60f6014](https://github.com/myparcelnl/woocommerce/commit/60f60140b21e81af1d9c7322fd9102ab2420d8b0))
* **admin:** fix console warning on modal open ([8040268](https://github.com/myparcelnl/woocommerce/commit/8040268d659a611c5127d79df935cca8302e355a))
* **admin:** fix cutoff time option throwing error ([8e84af9](https://github.com/myparcelnl/woocommerce/commit/8e84af99fc47022f0030725ed6459dff7dce19f2))
* **admin:** fix cutoff time translation ([87faab8](https://github.com/myparcelnl/woocommerce/commit/87faab8b44d52314626efca298d8a3f3446b05fe))
* **admin:** fix disabled and readonly logic on all inputs ([77a5492](https://github.com/myparcelnl/woocommerce/commit/77a549264923f07f93623c442752515581c26c55))
* **admin:** fix import of shipment label wrapper ([69dc9fa](https://github.com/myparcelnl/woocommerce/commit/69dc9fa4a45672519582f9476927f50a0d617107))
* **admin:** fix input widths exceeding containers ([e3381db](https://github.com/myparcelnl/woocommerce/commit/e3381dbaa2713030ff1236fd1a18e4f3800dae95))
* **admin:** fix label description notice in order grid ([63b631e](https://github.com/myparcelnl/woocommerce/commit/63b631ed4e0bc2379905b7137cd21e7f16778876))
* **admin:** fix rendering on elements with undefined as ref value ([5332974](https://github.com/myparcelnl/woocommerce/commit/533297497b64caf2fc066e6a71b06311c31cfc6e))
* **admin:** fix select boxes duplicating and options not being preselected ([2afaf62](https://github.com/myparcelnl/woocommerce/commit/2afaf6216464a253a82b8556dcd2ea9a761f1ef4))
* **admin:** fix select inputs not showing initial value ([85ceb13](https://github.com/myparcelnl/woocommerce/commit/85ceb13a4ef9b7f11844ecdc84b43f6f3dc3c422))
* **admin:** fix tooltips not being initialized ([43cfc49](https://github.com/myparcelnl/woocommerce/commit/43cfc49f299615fb4a812d120443669ee34dbc3e))
* **admin:** improve accessibility ([be58218](https://github.com/myparcelnl/woocommerce/commit/be5821841475e53eabe6e39a5bacb0c6380e3177))
* **admin:** improve dropdown button ([578dadb](https://github.com/myparcelnl/woocommerce/commit/578dadb2089d6f226a7023c7640eb19722c2a694))
* **admin:** improve select inputs ([7aab3b1](https://github.com/myparcelnl/woocommerce/commit/7aab3b1a4a0b19bf96f2c90a4dcd86ffb35e2411))
* **admin:** initialize tooltips properly ([2f46db0](https://github.com/myparcelnl/woocommerce/commit/2f46db05cfb553babdbe4975f38ccf07b362163c))
* **admin:** make button groups responsive ([#1003](https://github.com/myparcelnl/woocommerce/issues/1003)) ([654b8b9](https://github.com/myparcelnl/woocommerce/commit/654b8b9a9c543cdb288779a14691f26a26098b9f))
* **admin:** make the boxes and modals match wordpress better ([#1001](https://github.com/myparcelnl/woocommerce/issues/1001)) ([e95b663](https://github.com/myparcelnl/woocommerce/commit/e95b66344be5f44b9a848f46499b95378ce5e7a6))
* **admin:** translate form group text correctly ([e0ba9c0](https://github.com/myparcelnl/woocommerce/commit/e0ba9c0627aee8768aa68a789614f1571c8ae1fc))
* **admin:** update button style ([184b4b4](https://github.com/myparcelnl/woocommerce/commit/184b4b41c75a4f5b135ec7c60dacf6ef7c0365ea))
* **admin:** update components ([ba5f874](https://github.com/myparcelnl/woocommerce/commit/ba5f874969adf1b1f96ba8e95abb5e2431847e14))
* **admin:** update form group ([8a2e5ee](https://github.com/myparcelnl/woocommerce/commit/8a2e5eeaaeec9be1e8692a360ca6beb28116be03))
* **admin:** use different log level in production mode ([d2538f0](https://github.com/myparcelnl/woocommerce/commit/d2538f0d81ef74354d60471876c7918420a35ad7))
* **admin:** use the correct versions of js dependencies ([2eefa0e](https://github.com/myparcelnl/woocommerce/commit/2eefa0e012668af9d987606fc9e07a2124484812))
* **build:** fix plugin not being seen as new version of 4.x ([f832abb](https://github.com/myparcelnl/woocommerce/commit/f832abb8fba0e80f989e1542b23855caa70844a6))
* **cart:** fix incorrect reference to delivery options fees service ([27b0776](https://github.com/myparcelnl/woocommerce/commit/27b0776ccf76ed3112f7d9d7c97b87a44fbe2848))
* **checkout:** disable eori field warnings when they don't exist ([adc7527](https://github.com/myparcelnl/woocommerce/commit/adc7527699e4409fa5f02b14789c1b7c267c20c4))
* **checkout:** fix delivery options not being passed to backend ([33288e8](https://github.com/myparcelnl/woocommerce/commit/33288e8913ebd3d5c0a2f06a48357d522ff41f3c))
* **checkout:** fix delivery options not being saved to order ([e69766e](https://github.com/myparcelnl/woocommerce/commit/e69766e37588ee1b3cce8a0722e0e462d085e70a))
* **checkout:** fix empty billing address 1 ([#1005](https://github.com/myparcelnl/woocommerce/issues/1005)) ([b6ac3cc](https://github.com/myparcelnl/woocommerce/commit/b6ac3cc2ccc8dac291c7516caac6afd48430f406))
* **checkout:** fix error on submitting order ([dae7a45](https://github.com/myparcelnl/woocommerce/commit/dae7a459c171b05340ec420e89d59ead7539b443))
* **checkout:** fix javascript error on order received page ([c0477cc](https://github.com/myparcelnl/woocommerce/commit/c0477cc80aef2804de2dee23b9276dd85b4ee0d2))
* **checkout:** fix third party shipping methods not working with delivery options ([80357fc](https://github.com/myparcelnl/woocommerce/commit/80357fc7a6e7fc01d8e4e06faa855e736211da19))
* **checkout:** improve checkout logic ([26e3ee1](https://github.com/myparcelnl/woocommerce/commit/26e3ee14b411c1d7e268ebdede24fa70d0696149))
* **checkout:** prevent orders without incomplete address ([e416558](https://github.com/myparcelnl/woocommerce/commit/e4165584738eb287a0c036af3b5421972342c4e3))
* **components:** improve components ([942f0f3](https://github.com/myparcelnl/woocommerce/commit/942f0f33742f6e5c9b99f71c74d94b8e9e75d0c2))
* correctly show track & trace in account page ([ecb4fd8](https://github.com/myparcelnl/woocommerce/commit/ecb4fd8d529ab62892b1457b56645d4d46c82f3e))
* **customs-declaration:** pass correct package contents ([#1012](https://github.com/myparcelnl/woocommerce/issues/1012)) ([dde84e6](https://github.com/myparcelnl/woocommerce/commit/dde84e67b5ed7b24f62192e0c8c12defb7d4881a))
* **customs-declarations:** fix items amount ([#1010](https://github.com/myparcelnl/woocommerce/issues/1010)) ([545a8f1](https://github.com/myparcelnl/woocommerce/commit/545a8f19288b365585e20c029a3da7f51886d956))
* disable plugin peacefully when prerequisites are not met ([d891414](https://github.com/myparcelnl/woocommerce/commit/d8914140cff5df8c74cc8b7e5df2f1716c09081e))
* disable selectwoo for now because it won't work ([d20972e](https://github.com/myparcelnl/woocommerce/commit/d20972e3823bf3f6794e78d0d2b5b5abd5feadc8))
* do not show product settings in custom fields ([c21f951](https://github.com/myparcelnl/woocommerce/commit/c21f9512ab8e2ff6ad980dcde891977535268107))
* ensure numeric values at physical properties ([44e96f8](https://github.com/myparcelnl/woocommerce/commit/44e96f819d00cf7b34be0b3b3a4e1a78d80ba638))
* **export:** export label description ([ced065d](https://github.com/myparcelnl/woocommerce/commit/ced065d3533d8a1db2128bc2b12d58ec990986fc))
* fill settings that didnt't exist before migration ([bab3a36](https://github.com/myparcelnl/woocommerce/commit/bab3a3661e099206e0df652ce6f875acc2416d24))
* **filters:** allow not having a default value ([c580358](https://github.com/myparcelnl/woocommerce/commit/c580358b56e8cb9cd1dd3453933af9647601f232))
* fix changes in settings and account not being updated correctly ([514d990](https://github.com/myparcelnl/woocommerce/commit/514d9904e1e0fe49242a972f9f3e6909a94cd07b))
* fix custom fields not showing when they should ([8b1a3e1](https://github.com/myparcelnl/woocommerce/commit/8b1a3e1caeb1d70d18263230823ebbf50f0ad2f2))
* fix imports ([d8a49ce](https://github.com/myparcelnl/woocommerce/commit/d8a49ce10fa70b27835ae71176856bd47ccc6e23))
* fix naming error in bulk actions order mode ([4a7c17b](https://github.com/myparcelnl/woocommerce/commit/4a7c17b5c9ab7a71d520c58676722ffc2d12daf5))
* fix number error on "add order" page ([adadd92](https://github.com/myparcelnl/woocommerce/commit/adadd923aefebe557a2ad174970cb2b3be873973))
* fix undefined index warning ([7b82dd4](https://github.com/myparcelnl/woocommerce/commit/7b82dd45a87fad3f22ba77893d4f9a12ca1b3ec6))
* fix width of carrier settings not matching other tabs ([#1000](https://github.com/myparcelnl/woocommerce/issues/1000)) ([2f52c19](https://github.com/myparcelnl/woocommerce/commit/2f52c19bb9e70fa636644975f69cf6e85b420a2c))
* **hooks:** fix error on getting pdk frontend routes ([e0973e8](https://github.com/myparcelnl/woocommerce/commit/e0973e892b9047d7ea92b936c0dafd63421eb27f))
* **hpos:** fix order grid bulk actions not working ([d1eef35](https://github.com/myparcelnl/woocommerce/commit/d1eef35bca6681736d2b349ef37e4371378378ae))
* implement bootstrapper ([26feee5](https://github.com/myparcelnl/woocommerce/commit/26feee5f94896ca3de1b106b02da59c8ba61380e))
* improve components ([3a276a9](https://github.com/myparcelnl/woocommerce/commit/3a276a95a4dddf0d87977177316f7fe640bcffb7))
* improve prerequisites logic ([60b65f7](https://github.com/myparcelnl/woocommerce/commit/60b65f7780c4b39c38cf6a2756f3258b5fb506ab))
* increase order list column width ([73b8980](https://github.com/myparcelnl/woocommerce/commit/73b8980cf64f36cece74697cd98259fc2148f3a6))
* **meta:** fix settings link not showing on plugins page ([1ea10bc](https://github.com/myparcelnl/woocommerce/commit/1ea10bca4d71c81ef5fbaefa81a25273a778e393))
* **migration:** fix allow pickup locations setting not being migrated ([44e29d1](https://github.com/myparcelnl/woocommerce/commit/44e29d14d562280d502a50ec88b3b97d742c7108))
* **migration:** fix existing settings ([e7f6d11](https://github.com/myparcelnl/woocommerce/commit/e7f6d11240e28aec9ca50af2fd192f11f3316369))
* **migration:** fix inverted product settings search query ([e76028e](https://github.com/myparcelnl/woocommerce/commit/e76028eb3948e7a3b2a07595d4344c3a1bb1ee3f))
* **migration:** fix product settings migration failing ([2c32dd7](https://github.com/myparcelnl/woocommerce/commit/2c32dd7011600e097b022ac9508c87dc447770d1))
* **migration:** have weightservice work with all units ([f6e2614](https://github.com/myparcelnl/woocommerce/commit/f6e26144bfc944af5beb6c5d4f652b42c255dcab))
* **migration:** improve migration logic ([5364a02](https://github.com/myparcelnl/woocommerce/commit/5364a0239f284cc5ecaa824a2c47dcf6a5f6430a))
* **migration:** improve migrations ([e340e9d](https://github.com/myparcelnl/woocommerce/commit/e340e9dc8a230d64d93b7278b7c6a49801a51edf))
* **migration:** improve order migration ([bb8e7ef](https://github.com/myparcelnl/woocommerce/commit/bb8e7ef47427c03b05e58589ff3f7bd242adbbe7))
* **migration:** improve pdk migrations ([a970919](https://github.com/myparcelnl/woocommerce/commit/a97091983a40a9bf2354236e9a7fcde03dab33cd))
* **migration:** improve settings migration ([43e0c69](https://github.com/myparcelnl/woocommerce/commit/43e0c697c8b9148a9fa88f45cef3de7be8cbe665))
* **migration:** improve shipping methods migration ([b9383d3](https://github.com/myparcelnl/woocommerce/commit/b9383d34cfa486b100580e1828d6bf987d79900f))
* **migration:** migrate shipments to proper address fields ([f5db010](https://github.com/myparcelnl/woocommerce/commit/f5db0109f7e3cdcdac26a0326f52a1840ba759e9))
* **migration:** set delivery options date to null if it's empty ([#1020](https://github.com/myparcelnl/woocommerce/issues/1020)) ([58fc7a0](https://github.com/myparcelnl/woocommerce/commit/58fc7a002999ddfd6572ba95a97369d300fe35d6))
* **migration:** transform existing carrier settings ([7a9788b](https://github.com/myparcelnl/woocommerce/commit/7a9788b259e3a2b3d224e0b5a8f8772b9da95d51))
* **modal:** fix modal actions ([840f4ad](https://github.com/myparcelnl/woocommerce/commit/840f4adf82dc0828ef7439afb2ced191ac35284d))
* **notes:** get api identifier from order meta data ([91d87b3](https://github.com/myparcelnl/woocommerce/commit/91d87b311a20dabb768ed1ee9023751739eceaae))
* **orders:** convert weight to grams ([#1021](https://github.com/myparcelnl/woocommerce/issues/1021)) ([bd38290](https://github.com/myparcelnl/woocommerce/commit/bd38290ad041e9e32e6877294a5a872caa1270f3))
* **orders:** do not add return barcodes to order notes ([#998](https://github.com/myparcelnl/woocommerce/issues/998)) ([34ca572](https://github.com/myparcelnl/woocommerce/commit/34ca5727daf9ddba0a1214c385539faf53847ae5))
* **orders:** fix error when getting order via non-object ([0d39b5c](https://github.com/myparcelnl/woocommerce/commit/0d39b5cc0e9b3a301de5962915e673862c657c4a))
* **orders:** fix meta keys being saved multiple times ([98a4a3e](https://github.com/myparcelnl/woocommerce/commit/98a4a3edc6d07389434bbd4f9a02569f3c5799bd))
* **orders:** fix some errors when creating orders ([de47001](https://github.com/myparcelnl/woocommerce/commit/de470019fe2eee1a4277885a262d3a0bfdbf02fe))
* **orders:** fix state and missing address fields errors ([#1019](https://github.com/myparcelnl/woocommerce/issues/1019)) ([8ef3e53](https://github.com/myparcelnl/woocommerce/commit/8ef3e537a19a34928384740b65e7286e6991b286))
* **orders:** fix type error when country is missing ([f016b91](https://github.com/myparcelnl/woocommerce/commit/f016b916c7ed0c467e10473458945bab5056d3ad))
* **orders:** improve order note author mapping ([03e0a75](https://github.com/myparcelnl/woocommerce/commit/03e0a75aa7ed8e7c69f753e89c6a28cf6815e295))
* **plugin:** fix plugin being deactivated on upgrade ([3be182a](https://github.com/myparcelnl/woocommerce/commit/3be182a04176ac60c2117f0ad4ccdbcb857edfea))
* prefix bulk actions with "MyParcel" ([#999](https://github.com/myparcelnl/woocommerce/issues/999)) ([011376f](https://github.com/myparcelnl/woocommerce/commit/011376f3e95b45f6ace68a4c2b0ebf52e1683b18))
* prevent array to string conversion warning ([90c587d](https://github.com/myparcelnl/woocommerce/commit/90c587da80b39cc2e26420a62ae1386cbe1dda48))
* **product:** fix product settings not working ([756369e](https://github.com/myparcelnl/woocommerce/commit/756369e5848c429c1dc28ff84925a276a87ca02e))
* render settings page ([14e449d](https://github.com/myparcelnl/woocommerce/commit/14e449d69a74c3c29afce6561b189df2d9e9aa63))
* **request:** pass $_COOKIE and $_SERVER to converted requests ([0861ba3](https://github.com/myparcelnl/woocommerce/commit/0861ba36735c4e9d37c7d3269722ad173e11d1a6))
* **requests:** send correct user agent to myparcel api ([12e6afb](https://github.com/myparcelnl/woocommerce/commit/12e6afbb4aafe7d2ec081d2df00d3aaa1437df1f))
* **request:** update guzzle adapter ([02cd681](https://github.com/myparcelnl/woocommerce/commit/02cd681ab544271537dbb9d7e13c07757257ffe2))
* save shipment even when updated is not null ([e1e1849](https://github.com/myparcelnl/woocommerce/commit/e1e1849eb88a4839b170b358ad0345257bd52f92))
* set label description on order ([f7593f3](https://github.com/myparcelnl/woocommerce/commit/f7593f3c0bd7ac3ab23845413f53bcd1f84bb89c))
* set physical properties on pdkorder ([9056140](https://github.com/myparcelnl/woocommerce/commit/9056140712b31fb8fb5735133e3051017318c3d3))
* **settings:** correct keys for webhook settings ([902d7d5](https://github.com/myparcelnl/woocommerce/commit/902d7d52d9c5cb8356969fdbc6e3446f471bf099))
* **settings:** fix correct shipping methods not showing up ([1528585](https://github.com/myparcelnl/woocommerce/commit/1528585be4b0ebd0f1e4683c018b2409b4f7f8d8))
* **settings:** make drop off toggle reactive again ([#1008](https://github.com/myparcelnl/woocommerce/issues/1008)) ([ecb0cf0](https://github.com/myparcelnl/woocommerce/commit/ecb0cf067d57c30ac1e72e6b6c4cc7437adaf018))
* **settings:** prevent array to string conversion notice ([16227b1](https://github.com/myparcelnl/woocommerce/commit/16227b1dcd54b77bd4d5252572fc255a9469b80e))
* show notices through all_admin_notices hook ([613fb73](https://github.com/myparcelnl/woocommerce/commit/613fb731b9abeb935e80f1f0d0a2ddcf27f4a51b))
* update account settings after migration ([c4cffdd](https://github.com/myparcelnl/woocommerce/commit/c4cffdd5693305fc1c8cfdcdf1bf7161dbacf7d4))
* update fields ([1180fdf](https://github.com/myparcelnl/woocommerce/commit/1180fdf17e9f6a64ad144cc4c28eda99f420c07a))
* update frontend routes ([67b5aa1](https://github.com/myparcelnl/woocommerce/commit/67b5aa1acc7105d59c6fa38ed41a418311b68edd))
* update product settings ([f1f2fa3](https://github.com/myparcelnl/woocommerce/commit/f1f2fa32da8648d236198a3a159a379394e77a76))
* use correct option for automatic export ([4cc4959](https://github.com/myparcelnl/woocommerce/commit/4cc4959f63f42d267329a36b96ea20a130ccdf6b))
* wrap pdk admin init in window.onload ([899d14d](https://github.com/myparcelnl/woocommerce/commit/899d14d792efc38f88cd4941444b32159b3f48aa))


### :sparkles: New Features

* **account:** show delivery options in account ([9d515ad](https://github.com/myparcelnl/woocommerce/commit/9d515ad6d51326adb77f1b0f438fd98d2cb34ee9))
* add bulk actions ([c2ba715](https://github.com/myparcelnl/woocommerce/commit/c2ba715bc6f06caace880b5ab1c983d0a6eb87f3))
* add dhleuroplus and dhlparcelconnect ([643d297](https://github.com/myparcelnl/woocommerce/commit/643d297d94e72ca2eb23c28c4e1fca423063524e))
* **admin:** add subtext option to field ([5190f08](https://github.com/myparcelnl/woocommerce/commit/5190f08f2b827880317a3f4673290a42fd4edb54))
* **admin:** add text area and code editor component ([5adf95e](https://github.com/myparcelnl/woocommerce/commit/5adf95e290594b7e96af9ddbb656c02cea69f803))
* **admin:** improve button styles ([ba59c53](https://github.com/myparcelnl/woocommerce/commit/ba59c530a1633d5d046ca2a998adf21c03e8a2a4))
* **admin:** improve radio component ([7d1faf3](https://github.com/myparcelnl/woocommerce/commit/7d1faf326af289b2e4c33995d8a6d5d45b9f2003))
* **admin:** make select2 component work properly ([a42cadf](https://github.com/myparcelnl/woocommerce/commit/a42cadf5c523b690ba1b7f5a61ae36bfcb66fa95))
* **admin:** use native pdk product settings view ([da3b936](https://github.com/myparcelnl/woocommerce/commit/da3b93682cd35365a39cb3495b8753f682e82ff5))
* **checkout:** improve checkout logic ([cdfd72a](https://github.com/myparcelnl/woocommerce/commit/cdfd72a7a0dbcdc4aaf0ad86e0e7279e992d208c))
* **deps:** update [@myparcel-pdk](https://github.com/myparcel-pdk) packages ([82986b9](https://github.com/myparcelnl/woocommerce/commit/82986b9884cea8d2d065ccec77e48e1dfd883e66))
* **deps:** update @myparcel-pdk/admin ([4b19ca2](https://github.com/myparcelnl/woocommerce/commit/4b19ca22e9f9c34463a7ab8fa8bac4ae7e6a5d22))
* **deps:** update myparcelnl/pdk from 2.4.1 to 2.5.2 ([f09fabb](https://github.com/myparcelnl/woocommerce/commit/f09fabb28c8c22dbef66764367ada481d1fe3722))
* **deps:** update myparcelnl/pdk to from 2.3.0 to 2.4.1 ([a9e294e](https://github.com/myparcelnl/woocommerce/commit/a9e294e9393d392919aba31d765b4e943ce15ae5)), closes [#98](https://github.com/myparcelnl/woocommerce/issues/98) [#105](https://github.com/myparcelnl/woocommerce/issues/105)
* **deps:** update myparcelnl/pdk to v2.6.2 ([c9d367f](https://github.com/myparcelnl/woocommerce/commit/c9d367f08a6fee378abc1a32045eb8c6398a4edc))
* **deps:** upgrade @myparcel-pdk/* ([012f0dc](https://github.com/myparcelnl/woocommerce/commit/012f0dcf8118349e2ef95e34661652edecc0c19d))
* **deps:** upgrade myparcelnl/pdk to v2.19.0 ([a305f11](https://github.com/myparcelnl/woocommerce/commit/a305f114a1f47e892562c654de2d057b4717c2e1))
* **deps:** upgrade myparcelnl/pdk to v2.25.2 ([42b3fb0](https://github.com/myparcelnl/woocommerce/commit/42b3fb092a6b9ec449ac0baccb5f8f991f1e8092))
* **deps:** upgrade myparcelnl/pdk to v2.8.0 ([0c6385e](https://github.com/myparcelnl/woocommerce/commit/0c6385e90c692f8ea81fd243339b2368c629d040))
* **dhl:** add vat and eori fields for europlus ([#974](https://github.com/myparcelnl/woocommerce/issues/974)) ([0517dac](https://github.com/myparcelnl/woocommerce/commit/0517dacdb9492f0e3289968f171179fd1b96b2b0))
* **endpoints:** improve endpoint logic ([6e24a68](https://github.com/myparcelnl/woocommerce/commit/6e24a68d783c614876894446ec0a3092d8d69e3c))
* **migration:** add package type to product settings ([#1006](https://github.com/myparcelnl/woocommerce/issues/1006)) ([847552a](https://github.com/myparcelnl/woocommerce/commit/847552a3df16a4aa6ad7a588764ced2afa8824c9))
* move frontend checkout logic to pdk ([d64e053](https://github.com/myparcelnl/woocommerce/commit/d64e053d8aea0bfeee23f4dd9d9756d5e5b2e68f))
* move showing/hiding logic to app config ([4fc8a25](https://github.com/myparcelnl/woocommerce/commit/4fc8a25c8d23beff879d9c3e65134fe07477ea10))
* **notes:** add hook to post note after adding to order ([0588f17](https://github.com/myparcelnl/woocommerce/commit/0588f178a605f9dc27ba3ff8123a60cdd93c6531))
* **order:** get order notes ([a009cf7](https://github.com/myparcelnl/woocommerce/commit/a009cf7abbbcff47d5977507c9070f8da3945789))
* **orders:** change order status on label print ([#1016](https://github.com/myparcelnl/woocommerce/issues/1016)) ([fa5f5b6](https://github.com/myparcelnl/woocommerce/commit/fa5f5b6b234be9588a2bb23c1a4b0f383933a86b))
* **plugin:** add links to plugin meta ([7065195](https://github.com/myparcelnl/woocommerce/commit/70651959ddca19bb4ada83b6790fb9d16f18bcbb))
* prepend order note with text from settings ([68f6fe2](https://github.com/myparcelnl/woocommerce/commit/68f6fe22b2f9d2b70b1f2f6fd2cde5b0b48880ac))
* **product:** support variation product settings ([#996](https://github.com/myparcelnl/woocommerce/issues/996)) ([d4ae5b3](https://github.com/myparcelnl/woocommerce/commit/d4ae5b382ab0aa15083b96303abf536e4f7abf5d))
* rebuild entire plugin with pdk ([bbab1af](https://github.com/myparcelnl/woocommerce/commit/bbab1af1309c6611a9e0c269687377b0a6d81d5e))
* retrieve shipping methods ([cd5f5d3](https://github.com/myparcelnl/woocommerce/commit/cd5f5d3e588dd07fad99d96f0b8c86922a878510))
* **settings:** improve settings views ([106f09c](https://github.com/myparcelnl/woocommerce/commit/106f09c8c7d42246b8f45cf2c8455d7fc46f99b7))
* **shipments:** add barcode as order note ([#1004](https://github.com/myparcelnl/woocommerce/issues/1004)) ([7155cb8](https://github.com/myparcelnl/woocommerce/commit/7155cb82a980bca7f02dfa88ecf4eaa541126273))
* support woocommerce hpos ([74c9ccf](https://github.com/myparcelnl/woocommerce/commit/74c9ccf0aaf2da9f1bb037a163d946ff791304c6))
* **translations:** migrate to pdk builder translation logic ([542f25c](https://github.com/myparcelnl/woocommerce/commit/542f25cc023b54e9fb0f055ddbd24680e52c5a37))
* update dependencies ([d13568d](https://github.com/myparcelnl/woocommerce/commit/d13568df051fcaf022c76dfc84068f21ce90ca80))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.16](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.15...v5.0.0-alpha.16) (2023-07-18)


### :bug: Bug Fixes

* **admin:** fix input widths exceeding containers ([e3381db](https://github.com/myparcelnl/woocommerce/commit/e3381dbaa2713030ff1236fd1a18e4f3800dae95))
* **admin:** use different log level in production mode ([d2538f0](https://github.com/myparcelnl/woocommerce/commit/d2538f0d81ef74354d60471876c7918420a35ad7))
* **hpos:** fix order grid bulk actions not working ([d1eef35](https://github.com/myparcelnl/woocommerce/commit/d1eef35bca6681736d2b349ef37e4371378378ae))
* improve prerequisites logic ([60b65f7](https://github.com/myparcelnl/woocommerce/commit/60b65f7780c4b39c38cf6a2756f3258b5fb506ab))
* **meta:** fix settings link not showing on plugins page ([1ea10bc](https://github.com/myparcelnl/woocommerce/commit/1ea10bca4d71c81ef5fbaefa81a25273a778e393))


### :sparkles: New Features

* **order:** get order notes ([a009cf7](https://github.com/myparcelnl/woocommerce/commit/a009cf7abbbcff47d5977507c9070f8da3945789))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.15](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.14...v5.0.0-alpha.15) (2023-07-11)


### :sparkles: New Features

* **deps:** update [@myparcel-pdk](https://github.com/myparcel-pdk) packages ([82986b9](https://github.com/myparcelnl/woocommerce/commit/82986b9884cea8d2d065ccec77e48e1dfd883e66))
* **deps:** upgrade myparcelnl/pdk to v2.8.0 ([0c6385e](https://github.com/myparcelnl/woocommerce/commit/0c6385e90c692f8ea81fd243339b2368c629d040))
* support woocommerce hpos ([74c9ccf](https://github.com/myparcelnl/woocommerce/commit/74c9ccf0aaf2da9f1bb037a163d946ff791304c6))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.14](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.13...v5.0.0-alpha.14) (2023-07-04)


### :bug: Bug Fixes

* **components:** improve components ([942f0f3](https://github.com/myparcelnl/woocommerce/commit/942f0f33742f6e5c9b99f71c74d94b8e9e75d0c2))
* **requests:** send correct user agent to myparcel api ([12e6afb](https://github.com/myparcelnl/woocommerce/commit/12e6afbb4aafe7d2ec081d2df00d3aaa1437df1f))


### :sparkles: New Features

* **admin:** use native pdk product settings view ([da3b936](https://github.com/myparcelnl/woocommerce/commit/da3b93682cd35365a39cb3495b8753f682e82ff5))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.13](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.12...v5.0.0-alpha.13) (2023-07-03)


### :bug: Bug Fixes

* **checkout:** fix error on submitting order ([dae7a45](https://github.com/myparcelnl/woocommerce/commit/dae7a459c171b05340ec420e89d59ead7539b443))
* **migration:** fix inverted product settings search query ([e76028e](https://github.com/myparcelnl/woocommerce/commit/e76028eb3948e7a3b2a07595d4344c3a1bb1ee3f))
* **migration:** fix product settings migration failing ([2c32dd7](https://github.com/myparcelnl/woocommerce/commit/2c32dd7011600e097b022ac9508c87dc447770d1))
* **product:** fix product settings not working ([756369e](https://github.com/myparcelnl/woocommerce/commit/756369e5848c429c1dc28ff84925a276a87ca02e))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.13](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.12...v5.0.0-alpha.13) (2023-07-03)


### :bug: Bug Fixes

* **checkout:** fix error on submitting order ([dae7a45](https://github.com/myparcelnl/woocommerce/commit/dae7a459c171b05340ec420e89d59ead7539b443))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.12](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.11...v5.0.0-alpha.12) (2023-07-03)


### :sparkles: New Features

* **deps:** update myparcelnl/pdk from 2.4.1 to 2.5.2 ([f09fabb](https://github.com/myparcelnl/woocommerce/commit/f09fabb28c8c22dbef66764367ada481d1fe3722))
* **deps:** update myparcelnl/pdk to v2.6.2 ([c9d367f](https://github.com/myparcelnl/woocommerce/commit/c9d367f08a6fee378abc1a32045eb8c6398a4edc))


### :bug: Bug Fixes

* **admin:** fix cutoff time translation ([87faab8](https://github.com/myparcelnl/woocommerce/commit/87faab8b44d52314626efca298d8a3f3446b05fe))
* **admin:** fix disabled and readonly logic on all inputs ([77a5492](https://github.com/myparcelnl/woocommerce/commit/77a549264923f07f93623c442752515581c26c55))
* **admin:** fix label description notice in order grid ([63b631e](https://github.com/myparcelnl/woocommerce/commit/63b631ed4e0bc2379905b7137cd21e7f16778876))
* **checkout:** fix delivery options not being saved to order ([e69766e](https://github.com/myparcelnl/woocommerce/commit/e69766e37588ee1b3cce8a0722e0e462d085e70a))
* **checkout:** fix javascript error on order received page ([c0477cc](https://github.com/myparcelnl/woocommerce/commit/c0477cc80aef2804de2dee23b9276dd85b4ee0d2))
* **checkout:** fix third party shipping methods not working with delivery options ([80357fc](https://github.com/myparcelnl/woocommerce/commit/80357fc7a6e7fc01d8e4e06faa855e736211da19))
* **checkout:** prevent orders without incomplete address ([e416558](https://github.com/myparcelnl/woocommerce/commit/e4165584738eb287a0c036af3b5421972342c4e3))
* do not show product settings in custom fields ([c21f951](https://github.com/myparcelnl/woocommerce/commit/c21f9512ab8e2ff6ad980dcde891977535268107))
* fill settings that didnt't exist before migration ([bab3a36](https://github.com/myparcelnl/woocommerce/commit/bab3a3661e099206e0df652ce6f875acc2416d24))
* **migration:** fix allow pickup locations setting not being migrated ([44e29d1](https://github.com/myparcelnl/woocommerce/commit/44e29d14d562280d502a50ec88b3b97d742c7108))
* **migration:** improve pdk migrations ([a970919](https://github.com/myparcelnl/woocommerce/commit/a97091983a40a9bf2354236e9a7fcde03dab33cd))
* **migration:** improve shipping methods migration ([b9383d3](https://github.com/myparcelnl/woocommerce/commit/b9383d34cfa486b100580e1828d6bf987d79900f))
* **orders:** fix some errors when creating orders ([de47001](https://github.com/myparcelnl/woocommerce/commit/de470019fe2eee1a4277885a262d3a0bfdbf02fe))
* **plugin:** fix plugin being deactivated on upgrade ([3be182a](https://github.com/myparcelnl/woocommerce/commit/3be182a04176ac60c2117f0ad4ccdbcb857edfea))
* prevent array to string conversion warning ([90c587d](https://github.com/myparcelnl/woocommerce/commit/90c587da80b39cc2e26420a62ae1386cbe1dda48))
* set label description on order ([f7593f3](https://github.com/myparcelnl/woocommerce/commit/f7593f3c0bd7ac3ab23845413f53bcd1f84bb89c))
* **settings:** fix correct shipping methods not showing up ([1528585](https://github.com/myparcelnl/woocommerce/commit/1528585be4b0ebd0f1e4683c018b2409b4f7f8d8))
* **settings:** prevent array to string conversion notice ([16227b1](https://github.com/myparcelnl/woocommerce/commit/16227b1dcd54b77bd4d5252572fc255a9469b80e))
* update account settings after migration ([c4cffdd](https://github.com/myparcelnl/woocommerce/commit/c4cffdd5693305fc1c8cfdcdf1bf7161dbacf7d4))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.11](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.10...v5.0.0-alpha.11) (2023-06-08)


### :zap: Performance Improvements

* reduce amount of checks for account ([9b83be8](https://github.com/myparcelnl/woocommerce/commit/9b83be81947223718c0f2f96c309b23ca70f7923))


### :sparkles: New Features

* **deps:** update @myparcel-pdk/admin ([4b19ca2](https://github.com/myparcelnl/woocommerce/commit/4b19ca22e9f9c34463a7ab8fa8bac4ae7e6a5d22))
* **deps:** update myparcelnl/pdk to from 2.3.0 to 2.4.1 ([a9e294e](https://github.com/myparcelnl/woocommerce/commit/a9e294e9393d392919aba31d765b4e943ce15ae5)), closes [#98](https://github.com/myparcelnl/woocommerce/issues/98) [#105](https://github.com/myparcelnl/woocommerce/issues/105)


### :bug: Bug Fixes

* **admin:** use the correct versions of js dependencies ([2eefa0e](https://github.com/myparcelnl/woocommerce/commit/2eefa0e012668af9d987606fc9e07a2124484812))
* fix changes in settings and account not being updated correctly ([514d990](https://github.com/myparcelnl/woocommerce/commit/514d9904e1e0fe49242a972f9f3e6909a94cd07b))
* **migration:** improve settings migration ([43e0c69](https://github.com/myparcelnl/woocommerce/commit/43e0c697c8b9148a9fa88f45cef3de7be8cbe665))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.10](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.9...v5.0.0-alpha.10) (2023-06-02)


### :bug: Bug Fixes

* **migration:** have weightservice work with all units ([f6e2614](https://github.com/myparcelnl/woocommerce/commit/f6e26144bfc944af5beb6c5d4f652b42c255dcab))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.9](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.8...v5.0.0-alpha.9) (2023-06-02)


### :bug: Bug Fixes

* **migration:** transform existing carrier settings ([7a9788b](https://github.com/myparcelnl/woocommerce/commit/7a9788b259e3a2b3d224e0b5a8f8772b9da95d51))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.8](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.7...v5.0.0-alpha.8) (2023-06-02)


### :bug: Bug Fixes

* **migration:** fix existing settings ([e7f6d11](https://github.com/myparcelnl/woocommerce/commit/e7f6d11240e28aec9ca50af2fd192f11f3316369))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.7](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.6...v5.0.0-alpha.7) (2023-06-01)


### :bug: Bug Fixes

* **build:** fix plugin not being seen as new version of 4.x ([f832abb](https://github.com/myparcelnl/woocommerce/commit/f832abb8fba0e80f989e1542b23855caa70844a6))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.6](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.5...v5.0.0-alpha.6) (2023-06-01)


### :sparkles: New Features

* update dependencies ([d13568d](https://github.com/myparcelnl/woocommerce/commit/d13568df051fcaf022c76dfc84068f21ce90ca80))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.5](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.4...v5.0.0-alpha.5) (2023-05-17)


### :sparkles: New Features

* **checkout:** improve checkout logic ([cdfd72a](https://github.com/myparcelnl/woocommerce/commit/cdfd72a7a0dbcdc4aaf0ad86e0e7279e992d208c))
* **endpoints:** improve endpoint logic ([6e24a68](https://github.com/myparcelnl/woocommerce/commit/6e24a68d783c614876894446ec0a3092d8d69e3c))


### :bug: Bug Fixes

* **admin:** fix code editor not being used ([60f6014](https://github.com/myparcelnl/woocommerce/commit/60f60140b21e81af1d9c7322fd9102ab2420d8b0))
* **admin:** fix console warning on modal open ([8040268](https://github.com/myparcelnl/woocommerce/commit/8040268d659a611c5127d79df935cca8302e355a))
* **cart:** fix incorrect reference to delivery options fees service ([27b0776](https://github.com/myparcelnl/woocommerce/commit/27b0776ccf76ed3112f7d9d7c97b87a44fbe2848))
* **checkout:** fix delivery options not being passed to backend ([33288e8](https://github.com/myparcelnl/woocommerce/commit/33288e8913ebd3d5c0a2f06a48357d522ff41f3c))
* **checkout:** improve checkout logic ([26e3ee1](https://github.com/myparcelnl/woocommerce/commit/26e3ee14b411c1d7e268ebdede24fa70d0696149))
* correctly show track & trace in account page ([ecb4fd8](https://github.com/myparcelnl/woocommerce/commit/ecb4fd8d529ab62892b1457b56645d4d46c82f3e))
* **filters:** allow not having a default value ([c580358](https://github.com/myparcelnl/woocommerce/commit/c580358b56e8cb9cd1dd3453933af9647601f232))
* **request:** pass $_COOKIE and $_SERVER to converted requests ([0861ba3](https://github.com/myparcelnl/woocommerce/commit/0861ba36735c4e9d37c7d3269722ad173e11d1a6))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.4](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.3...v5.0.0-alpha.4) (2023-05-02)


### :bug: Bug Fixes

* add missing entry to container ([f4cb71b](https://github.com/myparcelnl/woocommerce/commit/f4cb71bb689836a1bd956b4d55658f521f0716ad))
* **admin:** do not render untranslated subtext ([fb0ccce](https://github.com/myparcelnl/woocommerce/commit/fb0ccce444cee85a7ca12a22f89f1b9e90a7207f))
* **admin:** fix cutoff time option throwing error ([8e84af9](https://github.com/myparcelnl/woocommerce/commit/8e84af99fc47022f0030725ed6459dff7dce19f2))
* **admin:** fix import of shipment label wrapper ([69dc9fa](https://github.com/myparcelnl/woocommerce/commit/69dc9fa4a45672519582f9476927f50a0d617107))
* **admin:** fix tooltips not being initialized ([43cfc49](https://github.com/myparcelnl/woocommerce/commit/43cfc49f299615fb4a812d120443669ee34dbc3e))
* **admin:** improve dropdown button ([578dadb](https://github.com/myparcelnl/woocommerce/commit/578dadb2089d6f226a7023c7640eb19722c2a694))
* **admin:** translate form group text correctly ([e0ba9c0](https://github.com/myparcelnl/woocommerce/commit/e0ba9c0627aee8768aa68a789614f1571c8ae1fc))
* **checkout:** disable eori field warnings when they don't exist ([adc7527](https://github.com/myparcelnl/woocommerce/commit/adc7527699e4409fa5f02b14789c1b7c267c20c4))
* fix custom fields not showing when they should ([8b1a3e1](https://github.com/myparcelnl/woocommerce/commit/8b1a3e1caeb1d70d18263230823ebbf50f0ad2f2))
* update fields ([1180fdf](https://github.com/myparcelnl/woocommerce/commit/1180fdf17e9f6a64ad144cc4c28eda99f420c07a))


### :sparkles: New Features

* **admin:** add subtext option to field ([5190f08](https://github.com/myparcelnl/woocommerce/commit/5190f08f2b827880317a3f4673290a42fd4edb54))
* **admin:** improve button styles ([ba59c53](https://github.com/myparcelnl/woocommerce/commit/ba59c530a1633d5d046ca2a998adf21c03e8a2a4))
* move frontend checkout logic to pdk ([d64e053](https://github.com/myparcelnl/woocommerce/commit/d64e053d8aea0bfeee23f4dd9d9756d5e5b2e68f))
* move showing/hiding logic to app config ([4fc8a25](https://github.com/myparcelnl/woocommerce/commit/4fc8a25c8d23beff879d9c3e65134fe07477ea10))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.3](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.2...v5.0.0-alpha.3) (2023-04-14)


### :sparkles: New Features

* **dhl:** add vat and eori fields for europlus ([#974](https://github.com/myparcelnl/woocommerce/issues/974)) ([22077a4](https://github.com/myparcelnl/woocommerce/commit/22077a4a8fa2d8485822f26d2c5f61b75bc7f7b7))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.2](https://github.com/myparcelnl/woocommerce/compare/v5.0.0-alpha.1...v5.0.0-alpha.2) (2023-04-14)


### :bug: Bug Fixes

* **admin:** fix rendering on elements with undefined as ref value ([1a090f9](https://github.com/myparcelnl/woocommerce/commit/1a090f9b30e4c94c09b134f89b87c7319fe1d4ca))
* **settings:** correct keys for webhook settings ([fad73c8](https://github.com/myparcelnl/woocommerce/commit/fad73c877b0747ed3af46bd8911268f8b6f50f93))

## ⚠ Warning ⚠

🚧 This version is **not ready for production use**. 🚧

This is the alpha release of the next major version of the MyParcel WooCommerce plugin. We've rewritten the plugin from scratch, using the [frontend] and [backend] of the MyParcel PDK (Plugin Development Kit).

For a safer experience, we recommend you to use the stable or release candidate version of the plugin. You can find the stable version in the [WordPress plugin directory]. The release candidate versions can be found in the [releases] section of this repository. They are versioned with a `-rc` suffix.

If you do choose to install this version, we would love to hear your feedback. Please report any issues you encounter using the [Bug report for v5.0.0-alpha.x form] or by sending an email to [support@myparcel.nl]

[Bug report for v5.0.0-alpha.x form]: https://github.com/myparcelnl/woocommerce/issues/new?labels=alpha&template=ZZ-BUG-REPORT-v5.yml
[WordPress plugin directory]: https://wordpress.org/plugins/woocommerce-myparcel/
[backend]: https://github.com/myparcelnl/pdk
[frontend]: https://github.com/myparcelnl/js-pdk
[releases]: https://github.com/myparcelnl/woocommerce/releases
[support@myparcel.nl]: mailto:support@myparcel.nl

## [5.0.0-alpha.1](https://github.com/myparcelnl/woocommerce/compare/v4.19.1...v5.0.0-alpha.1) (2023-04-13)


### ⚠ BREAKING CHANGES

* rebuild entire plugin with pdk

### :bug: Bug Fixes

* **admin:** add multi select input ([a0f4ea0](https://github.com/myparcelnl/woocommerce/commit/a0f4ea09d89d617269f5dcf3a53a83f515fe39cf))
* **admin:** change shipment label row transition ([92097cb](https://github.com/myparcelnl/woocommerce/commit/92097cbfcc01d552aa9095f0aa77412e4f3fa198))
* **admin:** fix select boxes duplicating and options not being preselected ([0a6b3c8](https://github.com/myparcelnl/woocommerce/commit/0a6b3c84cdf4dddbe8e28f181705a9495a704867))
* **admin:** improve select inputs ([5ebacab](https://github.com/myparcelnl/woocommerce/commit/5ebacab1e9f168f8d3d419a5a760d3a5bab75cab))
* **admin:** initialize tooltips properly ([87fb4e2](https://github.com/myparcelnl/woocommerce/commit/87fb4e2df7852620ef2276d818a3ec2011593780))
* **admin:** update button style ([d61f6cf](https://github.com/myparcelnl/woocommerce/commit/d61f6cf2d2d5b859423d0cf7f749f1b08b460bc8))
* **admin:** update components ([aec2ed9](https://github.com/myparcelnl/woocommerce/commit/aec2ed909f0fed38490d942ca26dddd12c4e1eee))
* **admin:** update form group ([ab28858](https://github.com/myparcelnl/woocommerce/commit/ab2885859d151cc3daa0701480c05a64d88acfee))
* disable selectwoo for now because it won't work ([7b1635b](https://github.com/myparcelnl/woocommerce/commit/7b1635b22190caaffde50c975e14d36e849cb4ea))
* ensure numeric values at physical properties ([a5d3ffe](https://github.com/myparcelnl/woocommerce/commit/a5d3ffeb6def12fc8796ad0110ebbf6207cf4584))
* fix imports ([306f4ec](https://github.com/myparcelnl/woocommerce/commit/306f4ec16e45963b9b5c945bd281affe3a90c978))
* fix naming error in bulk actions order mode ([1494d28](https://github.com/myparcelnl/woocommerce/commit/1494d289f041e98145e1b9e10f79d982778199bb))
* fix number error on "add order" page ([35e4485](https://github.com/myparcelnl/woocommerce/commit/35e448561ccca080fe45c6937065967efbbef89f))
* **hooks:** fix error on getting pdk frontend routes ([417916a](https://github.com/myparcelnl/woocommerce/commit/417916a66c804ea28f29d6a4a510df990b38b0bc))
* implement bootstrapper ([6846597](https://github.com/myparcelnl/woocommerce/commit/68465979a1971162b96a0b848cc4b2819bab9cc2))
* improve components ([7d4278e](https://github.com/myparcelnl/woocommerce/commit/7d4278e877fbb50f3610b3e33e44d5c8b487929b))
* increase order list column width ([84fb9c8](https://github.com/myparcelnl/woocommerce/commit/84fb9c891be6d4bc34223417facb8db7dfcb18a5))
* **migration:** improve migration logic ([d2f3962](https://github.com/myparcelnl/woocommerce/commit/d2f396298251255de28a71ffe6b7dfe7deb9c0eb))
* **modal:** fix modal actions ([a5e629d](https://github.com/myparcelnl/woocommerce/commit/a5e629deca098e23371f5fe95a6611bf4ba6df89))
* render settings page ([239f169](https://github.com/myparcelnl/woocommerce/commit/239f169e804d86314f0b9d7550a6259cb563a567))
* **request:** update guzzle adapter ([c77452d](https://github.com/myparcelnl/woocommerce/commit/c77452d3b70a1bb22e274a10062be23eee52d8d1))
* save shipment even when updated is not null ([4ffe30a](https://github.com/myparcelnl/woocommerce/commit/4ffe30a39e6d2964e002a188367b7a3fe94b393f))
* set physical properties on pdkorder ([d11d40e](https://github.com/myparcelnl/woocommerce/commit/d11d40e03728ae0996fcba9d53e30afcac60c604))
* show notices through all_admin_notices hook ([ce445ef](https://github.com/myparcelnl/woocommerce/commit/ce445effc0cdaf82c14cdc8b3050739b37bb1adb))
* update frontend routes ([8174f1f](https://github.com/myparcelnl/woocommerce/commit/8174f1f63f5ac7c55905df507410b6f94a201644))
* update product settings ([ef89e3a](https://github.com/myparcelnl/woocommerce/commit/ef89e3a4636ac32ee78d4bdaf00fe210ea976952))
* use correct option for automatic export ([9f176b6](https://github.com/myparcelnl/woocommerce/commit/9f176b66366cfed0813efe221cd617629fddcba9))
* wrap pdk admin init in window.onload ([64915cc](https://github.com/myparcelnl/woocommerce/commit/64915ccdcfefae75c2651ccc6ece563a3b0242ea))


### :sparkles: New Features

* add bulk actions ([b6818b8](https://github.com/myparcelnl/woocommerce/commit/b6818b8c372ee0c422c576544ffe2515e2fd0bde))
* add dhleuroplus and dhlparcelconnect ([3042371](https://github.com/myparcelnl/woocommerce/commit/30423712370d6925d8edc65b708e29b61e3bea02))
* **admin:** add text area and code editor component ([fc75a0d](https://github.com/myparcelnl/woocommerce/commit/fc75a0dfb7271220e207e0e56fcc544d251eddd3))
* **admin:** improve radio component ([57123ef](https://github.com/myparcelnl/woocommerce/commit/57123ef907cca48d505d4c0d7d572639748428b9))
* **admin:** make select2 component work properly ([d995187](https://github.com/myparcelnl/woocommerce/commit/d9951877a34b99b997b9d323eb2159a9c5073179))
* **plugin:** add links to plugin meta ([94f9c6c](https://github.com/myparcelnl/woocommerce/commit/94f9c6c7a2e57e27049e6b22ebec921bf770f2e4))
* prepend order note with text from settings ([bac9e19](https://github.com/myparcelnl/woocommerce/commit/bac9e1989c11563ba60cd617d75051b80674e49c))
* rebuild entire plugin with pdk ([d9d1459](https://github.com/myparcelnl/woocommerce/commit/d9d1459cea663f78e93167b7aced383c0c2a292a))
* retrieve shipping methods ([6bbe39a](https://github.com/myparcelnl/woocommerce/commit/6bbe39a139afa9bcd3aa03ec3b66601b9f94481c))
* **settings:** improve settings views ([1f96af0](https://github.com/myparcelnl/woocommerce/commit/1f96af0a61b1a00dfe4edce5123b034e8246fe0e))

## [4.19.1](https://github.com/myparcelnl/woocommerce/compare/v4.19.0...v4.19.1) (2023-04-13)


### :bug: Bug Fixes

* fix billing address is required error in checkout ([#970](https://github.com/myparcelnl/woocommerce/issues/970)) ([34aeff6](https://github.com/myparcelnl/woocommerce/commit/34aeff604e544724d1bec63d6f7064e8b196c65c))

## [4.19.0](https://github.com/myparcelnl/woocommerce/compare/v4.18.8...v4.19.0) (2023-04-11)


### :sparkles: New Features

* add barcode to fulfilment orders ([#966](https://github.com/myparcelnl/woocommerce/issues/966)) ([ab84873](https://github.com/myparcelnl/woocommerce/commit/ab84873f4d9f65d5b944ef7572c8a7d2ff1414e8))


### :bug: Bug Fixes

* default to postnl when carrier id is invalid ([#972](https://github.com/myparcelnl/woocommerce/issues/972)) ([2df693a](https://github.com/myparcelnl/woocommerce/commit/2df693a8cb1ba35c0d3775759d934511ea45dddb))

## [4.19.0-rc.1](https://github.com/myparcelnl/woocommerce/compare/v4.18.8...v4.19.0-rc.1) (2023-04-11)


### :sparkles: New Features

* add barcode to fulfilment orders ([#966](https://github.com/myparcelnl/woocommerce/issues/966)) ([ab84873](https://github.com/myparcelnl/woocommerce/commit/ab84873f4d9f65d5b944ef7572c8a7d2ff1414e8))


### :bug: Bug Fixes

* default to postnl when carrier id is invalid ([#972](https://github.com/myparcelnl/woocommerce/issues/972)) ([2df693a](https://github.com/myparcelnl/woocommerce/commit/2df693a8cb1ba35c0d3775759d934511ea45dddb))

## [4.19.0-rc.2](https://github.com/myparcelnl/woocommerce/compare/v4.19.0-rc.1...v4.19.0-rc.2) (2023-04-11)


### :bug: Bug Fixes

* default to postnl when carrier id is invalid ([#972](https://github.com/myparcelnl/woocommerce/issues/972)) ([2d01538](https://github.com/myparcelnl/woocommerce/commit/2d01538ccde469d11e5872ea7fe5fdc497a97268))

## [4.19.0-rc.1](https://github.com/myparcelnl/woocommerce/compare/v4.18.7...v4.19.0-rc.1) (2023-04-11)


### :sparkles: New Features

* add barcode to fulfilment orders ([#966](https://github.com/myparcelnl/woocommerce/issues/966)) ([0ab0f83](https://github.com/myparcelnl/woocommerce/commit/0ab0f8395a98ec0bd9537fe05d14f46d6e3d14d6))

## [4.18.8](https://github.com/myparcelnl/woocommerce/compare/v4.18.7...v4.18.8) (2023-04-11)


### :bug: Bug Fixes

* **frontend:** fix address_1 being empty with separate address fields ([#971](https://github.com/myparcelnl/woocommerce/issues/971)) ([47a22b5](https://github.com/myparcelnl/woocommerce/commit/47a22b58e401028246e00561b15c15742afbd865))

## [4.18.7](https://github.com/myparcelnl/woocommerce/compare/v4.18.6...v4.18.7) (2023-04-06)


### :bug: Bug Fixes

* **checkout:** do not show address inputs when allowRetry is false ([#967](https://github.com/myparcelnl/woocommerce/issues/967)) ([255e8b0](https://github.com/myparcelnl/woocommerce/commit/255e8b0daf62ddcd178ba5aea328d51b6a989da4))
* fix absent version in svn release script ([#964](https://github.com/myparcelnl/woocommerce/issues/964)) ([97cd557](https://github.com/myparcelnl/woocommerce/commit/97cd557baa74643c246479e1d68d7c3c384edaa9))
* prevent carrier 5 error when retrieving account settings ([#968](https://github.com/myparcelnl/woocommerce/issues/968)) ([634ecc2](https://github.com/myparcelnl/woocommerce/commit/634ecc2b4d023133c030b49e389a041937a4b504))
* prevent passing unknown carrier id ([#969](https://github.com/myparcelnl/woocommerce/issues/969)) ([49ad496](https://github.com/myparcelnl/woocommerce/commit/49ad49626fac5799e4ef41abecb7d01600030e78))

## [4.18.7-rc.3](https://github.com/myparcelnl/woocommerce/compare/v4.18.7-rc.2...v4.18.7-rc.3) (2023-04-06)


### :bug: Bug Fixes

* prevent passing unknown carrier id ([#969](https://github.com/myparcelnl/woocommerce/issues/969)) ([49ad496](https://github.com/myparcelnl/woocommerce/commit/49ad49626fac5799e4ef41abecb7d01600030e78))

## [4.18.7-rc.2](https://github.com/myparcelnl/woocommerce/compare/v4.18.7-rc.1...v4.18.7-rc.2) (2023-04-04)


### :bug: Bug Fixes

* prevent carrier 5 error when retrieving account settings ([#968](https://github.com/myparcelnl/woocommerce/issues/968)) ([634ecc2](https://github.com/myparcelnl/woocommerce/commit/634ecc2b4d023133c030b49e389a041937a4b504))

## [4.18.7-rc.1](https://github.com/myparcelnl/woocommerce/compare/v4.18.6...v4.18.7-rc.1) (2023-04-04)


### :bug: Bug Fixes

* **checkout:** do not show address inputs when allowRetry is false ([#967](https://github.com/myparcelnl/woocommerce/issues/967)) ([255e8b0](https://github.com/myparcelnl/woocommerce/commit/255e8b0daf62ddcd178ba5aea328d51b6a989da4))
* fix absent version in svn release script ([#964](https://github.com/myparcelnl/woocommerce/issues/964)) ([97cd557](https://github.com/myparcelnl/woocommerce/commit/97cd557baa74643c246479e1d68d7c3c384edaa9))

## [4.18.6](https://github.com/myparcelnl/woocommerce/compare/v4.18.5...v4.18.6) (2023-03-17)


### :bug: Bug Fixes

* allow delivery options with partial address ([#963](https://github.com/myparcelnl/woocommerce/issues/963)) ([d4f10cb](https://github.com/myparcelnl/woocommerce/commit/d4f10cb8e1c2d79a95142e17bf02761b013d4ace)), closes [#962](https://github.com/myparcelnl/woocommerce/issues/962)
* prevent javascript errors on order-pay ([#961](https://github.com/myparcelnl/woocommerce/issues/961)) ([847dd04](https://github.com/myparcelnl/woocommerce/commit/847dd049c21903cf0d92219292495bebbec9c427))

## [4.18.6-rc.1](https://github.com/myparcelnl/woocommerce/compare/v4.18.5...v4.18.6-rc.1) (2023-03-16)


### :bug: Bug Fixes

* prevent javascript errors on order-pay ([#961](https://github.com/myparcelnl/woocommerce/issues/961)) ([847dd04](https://github.com/myparcelnl/woocommerce/commit/847dd049c21903cf0d92219292495bebbec9c427))

## [4.18.5](https://github.com/myparcelnl/woocommerce/compare/v4.18.4...v4.18.5) (2023-03-10)


### :bug: Bug Fixes

* dont send number suffix with delivery options api call ([#960](https://github.com/myparcelnl/woocommerce/issues/960)) ([db77bca](https://github.com/myparcelnl/woocommerce/commit/db77bca38ef50cdce56e0cc3e4fc37665d34b942))

## [4.18.4](https://github.com/myparcelnl/woocommerce/compare/v4.18.3...v4.18.4) (2023-03-10)


### :bug: Bug Fixes

* fix numeric number suffix not working in checkout ([64db714](https://github.com/myparcelnl/woocommerce/commit/64db71474622a5690cbd4a994dd5c5700733cac4))

## [4.18.3](https://github.com/myparcelnl/woocommerce/compare/v4.18.2...v4.18.3) (2023-03-10)


### :bug: Bug Fixes

* fix operator error in order settings ([#959](https://github.com/myparcelnl/woocommerce/issues/959)) ([93e6ae1](https://github.com/myparcelnl/woocommerce/commit/93e6ae17386648918660a60777370658f9ee548f))

## [4.18.2](https://github.com/myparcelnl/woocommerce/compare/v4.18.1...v4.18.2) (2023-03-09)


### :bug: Bug Fixes

* change the return type of getTimestamp to match with the parent ([#958](https://github.com/myparcelnl/woocommerce/issues/958)) ([f56dab2](https://github.com/myparcelnl/woocommerce/commit/f56dab2003c47e00e6c4444c343e6dcbbb1b6e33))

## [4.18.2-rc.1](https://github.com/myparcelnl/woocommerce/compare/v4.18.1...v4.18.2-rc.1) (2023-03-09)


### :bug: Bug Fixes

* change the return type of getTimestamp to match with the parent ([#958](https://github.com/myparcelnl/woocommerce/issues/958)) ([f56dab2](https://github.com/myparcelnl/woocommerce/commit/f56dab2003c47e00e6c4444c343e6dcbbb1b6e33))

## [4.18.1](https://github.com/myparcelnl/woocommerce/compare/v4.18.0...v4.18.1) (2023-03-09)


### :bug: Bug Fixes

* fix delivery options for dhl parcelconnect and dhl europlus ([#957](https://github.com/myparcelnl/woocommerce/issues/957)) ([5753745](https://github.com/myparcelnl/woocommerce/commit/575374509eacd1c865e28c641813c2ddee8014ad))

## [4.18.0](https://github.com/myparcelnl/woocommerce/compare/v4.17.0...v4.18.0) (2023-03-01)


### :bug: Bug Fixes

* close inline style properly ([#954](https://github.com/myparcelnl/woocommerce/issues/954)) ([32b80fb](https://github.com/myparcelnl/woocommerce/commit/32b80fbf2758faad0d7780da59558422c787da71)), closes [#950](https://github.com/myparcelnl/woocommerce/issues/950)


### :sparkles: New Features

* add carrier dhl europlus and dhl parcelconnect ([#955](https://github.com/myparcelnl/woocommerce/issues/955)) ([6b7b89b](https://github.com/myparcelnl/woocommerce/commit/6b7b89b1a2d740649afe3da11233f254aaec7196))

## [4.17.1-rc.1](https://github.com/myparcelnl/woocommerce/compare/v4.17.0...v4.17.1-rc.1) (2023-02-21)


### :bug: Bug Fixes

* close inline style properly ([#954](https://github.com/myparcelnl/woocommerce/issues/954)) ([32b80fb](https://github.com/myparcelnl/woocommerce/commit/32b80fbf2758faad0d7780da59558422c787da71)), closes [#950](https://github.com/myparcelnl/woocommerce/issues/950)

## [4.17.0](https://github.com/myparcelnl/woocommerce/compare/v4.16.4...v4.17.0) (2023-02-21)


### :sparkles: New Features

* add customizable string for address not found ([#945](https://github.com/myparcelnl/woocommerce/issues/945)) ([1622340](https://github.com/myparcelnl/woocommerce/commit/162234017e70e05df2faabdd4d86d53ea76259dd))


### :bug: Bug Fixes

* delete address not found prompt from checkout ([#946](https://github.com/myparcelnl/woocommerce/issues/946)) ([42184e2](https://github.com/myparcelnl/woocommerce/commit/42184e2a2c306e377027aa4af1e3bae6fee91c9f))
* fix belgian number suffix ([#923](https://github.com/myparcelnl/woocommerce/issues/923)) ([be54ad3](https://github.com/myparcelnl/woocommerce/commit/be54ad38cb8dd0644ab3f86a4973ef9e9051af01))
* fix type error in getting value of option ([#952](https://github.com/myparcelnl/woocommerce/issues/952)) ([fa618d4](https://github.com/myparcelnl/woocommerce/commit/fa618d461a7c48742cc3e10e48f146cc1bd8afcd))
* **migration:** pass cc argument to insurance function ([#953](https://github.com/myparcelnl/woocommerce/issues/953)) ([afb59d5](https://github.com/myparcelnl/woocommerce/commit/afb59d5cd718da0840bb615cd8114b5874ece57e))
* only read shipping class term when available ([#951](https://github.com/myparcelnl/woocommerce/issues/951)) ([ff9792b](https://github.com/myparcelnl/woocommerce/commit/ff9792b43da0addd99f4f863d091b2f4ea51cb63))
* prevent type error in flat rate evaluate cost ([#949](https://github.com/myparcelnl/woocommerce/issues/949)) ([307c140](https://github.com/myparcelnl/woocommerce/commit/307c1407fdbac40a0e474c46ef4c0960c5b99eec))
* prevent undefined array keys ([#933](https://github.com/myparcelnl/woocommerce/issues/933)) ([5398153](https://github.com/myparcelnl/woocommerce/commit/5398153fdec92d7fe3ef07b38543ae44c393ff89))
* prevent widget config errors ([#947](https://github.com/myparcelnl/woocommerce/issues/947)) ([b26639b](https://github.com/myparcelnl/woocommerce/commit/b26639b5b179a366d896a6e081988f490e40f472))

## [4.17.0-rc.7](https://github.com/myparcelnl/woocommerce/compare/v4.17.0-rc.6...v4.17.0-rc.7) (2023-02-20)


### :bug: Bug Fixes

* **migration:** pass cc argument to insurance function ([#953](https://github.com/myparcelnl/woocommerce/issues/953)) ([afb59d5](https://github.com/myparcelnl/woocommerce/commit/afb59d5cd718da0840bb615cd8114b5874ece57e))

## [4.17.0-rc.6](https://github.com/myparcelnl/woocommerce/compare/v4.17.0-rc.5...v4.17.0-rc.6) (2023-02-16)


### :bug: Bug Fixes

* fix type error in getting value of option ([#952](https://github.com/myparcelnl/woocommerce/issues/952)) ([fa618d4](https://github.com/myparcelnl/woocommerce/commit/fa618d461a7c48742cc3e10e48f146cc1bd8afcd))

## [4.17.0-rc.5](https://github.com/myparcelnl/woocommerce/compare/v4.17.0-rc.4...v4.17.0-rc.5) (2023-02-03)


### :bug: Bug Fixes

* only read shipping class term when available ([#951](https://github.com/myparcelnl/woocommerce/issues/951)) ([ff9792b](https://github.com/myparcelnl/woocommerce/commit/ff9792b43da0addd99f4f863d091b2f4ea51cb63))

## [4.17.0-rc.4](https://github.com/myparcelnl/woocommerce/compare/v4.17.0-rc.3...v4.17.0-rc.4) (2023-01-25)


### :bug: Bug Fixes

* prevent type error in flat rate evaluate cost ([#949](https://github.com/myparcelnl/woocommerce/issues/949)) ([307c140](https://github.com/myparcelnl/woocommerce/commit/307c1407fdbac40a0e474c46ef4c0960c5b99eec))

## [4.17.0-rc.3](https://github.com/myparcelnl/woocommerce/compare/v4.17.0-rc.2...v4.17.0-rc.3) (2023-01-12)


### :bug: Bug Fixes

* prevent widget config errors ([#947](https://github.com/myparcelnl/woocommerce/issues/947)) ([40f7eb4](https://github.com/myparcelnl/woocommerce/commit/40f7eb41af52a3b6315173a98f6220c79fa3a3ca))

## [4.17.0-rc.2](https://github.com/myparcelnl/woocommerce/compare/v4.17.0-rc.1...v4.17.0-rc.2) (2023-01-11)


### :bug: Bug Fixes

* fix belgian number suffix ([#923](https://github.com/myparcelnl/woocommerce/issues/923)) ([0a5f73e](https://github.com/myparcelnl/woocommerce/commit/0a5f73e7104d9a3ed86ac58ec13293bc8fdae840))

## [4.17.0-rc.1](https://github.com/myparcelnl/woocommerce/compare/v4.16.5-rc.1...v4.17.0-rc.1) (2023-01-09)


### :sparkles: New Features

* add customizable string for address not found ([#945](https://github.com/myparcelnl/woocommerce/issues/945)) ([8504476](https://github.com/myparcelnl/woocommerce/commit/85044763e0e4ef3f7023a782e7aadd4251c87ec1))


### :bug: Bug Fixes

* delete address not found prompt from checkout ([#946](https://github.com/myparcelnl/woocommerce/issues/946)) ([5762755](https://github.com/myparcelnl/woocommerce/commit/57627552a1875b7c7994d1dcffd09a891f6f0ccc))

## [4.16.5-rc.1](https://github.com/myparcelnl/woocommerce/compare/v4.16.4...v4.16.5-rc.1) (2023-01-09)


### :bug: Bug Fixes

* prevent undefined array keys ([#933](https://github.com/myparcelnl/woocommerce/issues/933)) ([0234c1a](https://github.com/myparcelnl/woocommerce/commit/0234c1aa93e283ca92fce9948b10e493fbbf16d8))

## [4.16.5-develop.1](https://github.com/myparcelnl/woocommerce/compare/v4.16.4...v4.16.5-develop.1) (2023-01-09)


### :bug: Bug Fixes

* prevent undefined array keys ([#933](https://github.com/myparcelnl/woocommerce/issues/933)) ([f8177eb](https://github.com/myparcelnl/woocommerce/commit/f8177eb9bb3efd24d0845b1bca76465a217f9157))

## [4.16.4](https://github.com/myparcelnl/woocommerce/compare/v4.16.3...v4.16.4) (2023-01-05)


### :bug: Bug Fixes

* convert product dimensions from measurement unit ([#941](https://github.com/myparcelnl/woocommerce/issues/941)) ([f7c1d6d](https://github.com/myparcelnl/woocommerce/commit/f7c1d6d4aed6b9e364440e4cf74257051b56331a))

## [4.16.3](https://github.com/myparcelnl/woocommerce/compare/v4.16.2...v4.16.3) (2023-01-04)


### :bug: Bug Fixes

* prevent passing null to datetime ([#940](https://github.com/myparcelnl/woocommerce/issues/940)) ([be004a9](https://github.com/myparcelnl/woocommerce/commit/be004a99f51fccadb1806587666342d3305a6201))

## [4.16.2](https://github.com/myparcelnl/woocommerce/compare/v4.16.1...v4.16.2) (2023-01-04)


### :bug: Bug Fixes

* fix several type errors ([#939](https://github.com/myparcelnl/woocommerce/issues/939)) ([10ea21e](https://github.com/myparcelnl/woocommerce/commit/10ea21ef6a54410aef03b755b2a2ca1fcaff5197)), closes [#938](https://github.com/myparcelnl/woocommerce/issues/938)

## [4.16.1](https://github.com/myparcelnl/woocommerce/compare/v4.16.0...v4.16.1) (2023-01-03)


### :bug: Bug Fixes

* show order detail even when package type is integer ([#935](https://github.com/myparcelnl/woocommerce/issues/935)) ([4e533bd](https://github.com/myparcelnl/woocommerce/commit/4e533bdff894880128e79d87a22e6961cb7109f8))

## [4.16.0](https://github.com/myparcelnl/woocommerce/compare/v4.15.2...v4.16.0) (2023-01-03)


### :bug: Bug Fixes

* always load account settings during upgrade ([#916](https://github.com/myparcelnl/woocommerce/issues/916)) ([2d87f4e](https://github.com/myparcelnl/woocommerce/commit/2d87f4e2ed8a0258b411191562e3d4dbf7a53053))
* fix deprecation warning on belgian shipments ([#929](https://github.com/myparcelnl/woocommerce/issues/929)) ([b80b507](https://github.com/myparcelnl/woocommerce/commit/b80b507d7f6726e2d4737263e752c85afacdebdf))
* fix exclude billing with digital download ([#930](https://github.com/myparcelnl/woocommerce/issues/930)) ([00d8c3b](https://github.com/myparcelnl/woocommerce/commit/00d8c3bbfb018718ac9585641ec7a26a34e7987e))
* fix str_replace fatal error during checkout ([#931](https://github.com/myparcelnl/woocommerce/issues/931)) ([152a5b9](https://github.com/myparcelnl/woocommerce/commit/152a5b99e6eef1a09e72083ca71628755b9da757))
* fix type error on fresh install ([0e229ab](https://github.com/myparcelnl/woocommerce/commit/0e229ab5e6af4222948364992e45f5b6dc0be4bb))
* prevent error substr expects parameter 1 to be string ([#927](https://github.com/myparcelnl/woocommerce/issues/927)) ([5e03b1f](https://github.com/myparcelnl/woocommerce/commit/5e03b1f9d23a57b1f6526e4c7a2510ee18bddeef))


### :sparkles: New Features

* add carrier dhl ([#913](https://github.com/myparcelnl/woocommerce/issues/913)) ([d18e8aa](https://github.com/myparcelnl/woocommerce/commit/d18e8aacb5bdbc4c7ec98d9a56c4a915265100a7))
* add insurance options for eu shipments ([#922](https://github.com/myparcelnl/woocommerce/issues/922)) ([62bf761](https://github.com/myparcelnl/woocommerce/commit/62bf761feff2113447419ff70ff00abf26e75cc8))

## [4.16.0-rc.2](https://github.com/myparcelnl/woocommerce/compare/v4.16.0-rc.1...v4.16.0-rc.2) (2022-12-20)


### :bug: Bug Fixes

* fix deprecation warning on belgian shipments ([#929](https://github.com/myparcelnl/woocommerce/issues/929)) ([b80b507](https://github.com/myparcelnl/woocommerce/commit/b80b507d7f6726e2d4737263e752c85afacdebdf))
* fix exclude billing with digital download ([#930](https://github.com/myparcelnl/woocommerce/issues/930)) ([00d8c3b](https://github.com/myparcelnl/woocommerce/commit/00d8c3bbfb018718ac9585641ec7a26a34e7987e))
* prevent error substr expects parameter 1 to be string ([#927](https://github.com/myparcelnl/woocommerce/issues/927)) ([5e03b1f](https://github.com/myparcelnl/woocommerce/commit/5e03b1f9d23a57b1f6526e4c7a2510ee18bddeef))

## [4.16.0-rc.1](https://github.com/myparcelnl/woocommerce/compare/v4.15.3-rc.1...v4.16.0-rc.1) (2022-12-07)


### :sparkles: New Features

* add carrier dhl ([#913](https://github.com/myparcelnl/woocommerce/issues/913)) ([d18e8aa](https://github.com/myparcelnl/woocommerce/commit/d18e8aacb5bdbc4c7ec98d9a56c4a915265100a7))

## [4.15.3-rc.1](https://github.com/myparcelnl/woocommerce/compare/v4.15.2...v4.15.3-rc.1) (2022-12-07)


### :bug: Bug Fixes

* always load account settings during upgrade ([#916](https://github.com/myparcelnl/woocommerce/issues/916)) ([2d87f4e](https://github.com/myparcelnl/woocommerce/commit/2d87f4e2ed8a0258b411191562e3d4dbf7a53053))

### [4.15.2](https://github.com/myparcelnl/woocommerce/compare/v4.15.1...v4.15.2) (2022-12-06)


### :bug: Bug Fixes

* provide correct version to svn deploy ([#919](https://github.com/myparcelnl/woocommerce/issues/919)) ([6109c24](https://github.com/myparcelnl/woocommerce/commit/6109c24dd2ebb6a47154382d4678ae17ce2429fd))

### [4.15.1](https://github.com/myparcelnl/woocommerce/compare/v4.15.0...v4.15.1) (2022-11-22)


### :bug: Bug Fixes

* prevent error myparcel admin already exists ([dcbeb19](https://github.com/myparcelnl/woocommerce/commit/dcbeb19ce3a45175727ef4eec37b63467347e66b))

## [4.15.0](https://github.com/myparcelnl/woocommerce/compare/v4.14.0...v4.15.0) (2022-11-22)


### :sparkles: New Features

* add modal option for myparcel orders only ([#869](https://github.com/myparcelnl/woocommerce/issues/869)) ([77b1121](https://github.com/myparcelnl/woocommerce/commit/77b112195e602210a3646adc87ba85f30188e463))
* show digital stamp weight in order view ([#896](https://github.com/myparcelnl/woocommerce/issues/896)) ([036c34c](https://github.com/myparcelnl/woocommerce/commit/036c34c9f0b4b204163d448e2df8e72a72a51c96))
* show test connection button ([#881](https://github.com/myparcelnl/woocommerce/issues/881)) ([6719f0a](https://github.com/myparcelnl/woocommerce/commit/6719f0a6d7133d2f917b7e616a9e8189acadf097))


### :bug: Bug Fixes

* allow link to order detail from widget ([#906](https://github.com/myparcelnl/woocommerce/issues/906)) ([0073155](https://github.com/myparcelnl/woocommerce/commit/0073155d98a1e3ce47e0ecf9dc6ea0a20685e5f8))
* allow return options ([#915](https://github.com/myparcelnl/woocommerce/issues/915)) ([373f5d6](https://github.com/myparcelnl/woocommerce/commit/373f5d658be77ab2d61d34aad1811d948c418bc9))
* get correct hs code and country of origin for variation ([#910](https://github.com/myparcelnl/woocommerce/issues/910)) ([aaefdd1](https://github.com/myparcelnl/woocommerce/commit/aaefdd178230ab6c79b7282d620dc1f3cd77cfc3))
* load account settings during upgrade ([#914](https://github.com/myparcelnl/woocommerce/issues/914)) ([0bc1532](https://github.com/myparcelnl/woocommerce/commit/0bc15322b40308e897c15f6622d02effc86ba156))
* prevent dismissed message from reappearing ([#898](https://github.com/myparcelnl/woocommerce/issues/898)) ([7066bfa](https://github.com/myparcelnl/woocommerce/commit/7066bfab45bd3da6882ec334071760b67b9e2792))
* prevent unnecessary automatic export ([#895](https://github.com/myparcelnl/woocommerce/issues/895)) ([f04e1f2](https://github.com/myparcelnl/woocommerce/commit/f04e1f206e432000cd307e4dc9bc5cf18f22510f))
* remove barcode from refund mail ([#894](https://github.com/myparcelnl/woocommerce/issues/894)) ([be0bcda](https://github.com/myparcelnl/woocommerce/commit/be0bcdaee53159ac47f1b5f1f8e73ee7b9ce3a04))
* remove carrier instabox ([#912](https://github.com/myparcelnl/woocommerce/issues/912)) ([b59e3ef](https://github.com/myparcelnl/woocommerce/commit/b59e3efeb2e3d3f98cc414686646ab41d8b0f054))
* show tracktrace in order email when no refunds ([0bcd634](https://github.com/myparcelnl/woocommerce/commit/0bcd634f4308321b9196c6d144aee7344601fbb9))
* show tracktrace in order email when no refunds ([#908](https://github.com/myparcelnl/woocommerce/issues/908)) ([7239dfb](https://github.com/myparcelnl/woocommerce/commit/7239dfb4cccc54bbaf961cba264b987f7ac97c39))

## [4.14.0](https://github.com/myparcelnl/woocommerce/compare/v4.13.3...v4.14.0) (2022-10-11)


### :sparkles: New Features

* add support for advanced shipping ([#879](https://github.com/myparcelnl/woocommerce/issues/879)) ([1219564](https://github.com/myparcelnl/woocommerce/commit/1219564e2a0411d5b5521462b539f06b6087c786))


### :bug: Bug Fixes

* fix belgian box numbers ([#883](https://github.com/myparcelnl/woocommerce/issues/883)) ([70b436e](https://github.com/myparcelnl/woocommerce/commit/70b436e99fb8f117edd65193d2c3a117ea2ed923))
* fix digital stamp default weight ([#871](https://github.com/myparcelnl/woocommerce/issues/871)) ([e6d696a](https://github.com/myparcelnl/woocommerce/commit/e6d696abc63309629ec3550226a605eadc85ac81))
* make deploy workflow reusable ([#888](https://github.com/myparcelnl/woocommerce/issues/888)) ([a9461e1](https://github.com/myparcelnl/woocommerce/commit/a9461e128d1907a58f7fd4339dc7cf579c15dd1d))
* prevent automatic export when local pickup ([#887](https://github.com/myparcelnl/woocommerce/issues/887)) ([42253fc](https://github.com/myparcelnl/woocommerce/commit/42253fc85c574bc0e16fc2187088bf64f1a2feb9))

### [4.13.3](https://github.com/myparcelnl/woocommerce/compare/v4.13.2...v4.13.3) (2022-09-06)


### :bug: Bug Fixes

* prevent external translations ([#873](https://github.com/myparcelnl/woocommerce/issues/873)) ([a67f404](https://github.com/myparcelnl/woocommerce/commit/a67f4049f788c2b180b3d208d50f6195c3990f4e))
* prevent shipping method from defaulting to package ([#865](https://github.com/myparcelnl/woocommerce/issues/865)) ([45409e7](https://github.com/myparcelnl/woocommerce/commit/45409e7af59e76d7b7ff6f5798dee57305c61da3))
* use shift instead of index ([#866](https://github.com/myparcelnl/woocommerce/issues/866)) ([d31bb15](https://github.com/myparcelnl/woocommerce/commit/d31bb15abb52f87046dbcf3bea7f14ad1c2eabd1))

### [4.13.2](https://github.com/myparcelnl/woocommerce/compare/v4.13.1...v4.13.2) (2022-08-16)


### :bug: Bug Fixes

* catch orders without shipping method ([#864](https://github.com/myparcelnl/woocommerce/issues/864)) ([2368a8e](https://github.com/myparcelnl/woocommerce/commit/2368a8e4f4483d5accf10d95b838ce45fa458b50))

### [4.13.1](https://github.com/myparcelnl/woocommerce/compare/v4.13.0...v4.13.1) (2022-08-16)


### :bug: Bug Fixes

* use local pickup method id ([#860](https://github.com/myparcelnl/woocommerce/issues/860)) ([56fb80d](https://github.com/myparcelnl/woocommerce/commit/56fb80d0c0ffe395edeaf5573934da486b88268e))

## [4.13.0](https://github.com/myparcelnl/woocommerce/compare/v4.12.2...v4.13.0) (2022-08-02)


### :sparkles: New Features

* add myparcel block to dashboard ([#854](https://github.com/myparcelnl/woocommerce/issues/854)) ([c36dd09](https://github.com/myparcelnl/woocommerce/commit/c36dd09f39f610c47ed6196eca106f007f020139))


### :bug: Bug Fixes

* allow numbers as suffix using myparcel address fields ([#850](https://github.com/myparcelnl/woocommerce/issues/850)) ([3b5d712](https://github.com/myparcelnl/woocommerce/commit/3b5d712c03171563605ab9cd3a5f38211b3242ce))
* fix tablerate plugin mailbox ([#859](https://github.com/myparcelnl/woocommerce/issues/859)) ([09f1d08](https://github.com/myparcelnl/woocommerce/commit/09f1d08afaaf022bb6af0633c57be4ba21cbfcae))
* fix track trace link in order summary ([#856](https://github.com/myparcelnl/woocommerce/issues/856)) ([c3b03ea](https://github.com/myparcelnl/woocommerce/commit/c3b03eab658f14e5ba9235d8dc297e62100694b2))
* **om:** error when delivery options absent ([#845](https://github.com/myparcelnl/woocommerce/issues/845)) ([e964884](https://github.com/myparcelnl/woocommerce/commit/e964884ff480be0d1ca8394c00b3517c2d1e6aa2))
* only subscribe to webhook when api key changed ([#846](https://github.com/myparcelnl/woocommerce/issues/846)) ([8a2b92c](https://github.com/myparcelnl/woocommerce/commit/8a2b92ccd585e7388ca3026b9e54ab9e57303671))
* php warnings undefined array key ([#848](https://github.com/myparcelnl/woocommerce/issues/848)) ([7a82b33](https://github.com/myparcelnl/woocommerce/commit/7a82b33abd58a547e13f152fb196bd50a6f71000))
* prevent exporting of orders with local pickup as shipping method ([#858](https://github.com/myparcelnl/woocommerce/issues/858)) ([508dc49](https://github.com/myparcelnl/woocommerce/commit/508dc49d2bd02c8bd015a5ed6d2a42b14238bc6e))
* remove age check from eu and row consignments ([#855](https://github.com/myparcelnl/woocommerce/issues/855)) ([2e6a9eb](https://github.com/myparcelnl/woocommerce/commit/2e6a9eb9d412688665c8fbf2a96678e10780a2c0))

### [4.12.2](https://github.com/myparcelnl/woocommerce/compare/v4.12.1...v4.12.2) (2022-06-20)


### :bug: Bug Fixes

* add missing file to final zip ([bbb90b6](https://github.com/myparcelnl/woocommerce/commit/bbb90b646452399184a3b961063af5a15ab8f90f))

### [4.12.1](https://github.com/myparcelnl/woocommerce/compare/v4.12.0...v4.12.1) (2022-06-20)


### :bug: Bug Fixes

* **om:** fix error when creating webhook ([#840](https://github.com/myparcelnl/woocommerce/issues/840)) ([2b09ef4](https://github.com/myparcelnl/woocommerce/commit/2b09ef4236a9f3960d2a3e180c73bdedadb42cca))

## [4.12.0](https://github.com/myparcelnl/woocommerce/compare/v4.11.0...v4.12.0) (2022-06-14)


### Features

* add default weight for digital stamp ([#832](https://github.com/myparcelnl/woocommerce/issues/832)) ([702f467](https://github.com/myparcelnl/woocommerce/commit/702f46796c626252fe4d66d0a98e0edcaf82c6a6))
* add option to save customer address ([#827](https://github.com/myparcelnl/woocommerce/issues/827)) ([4c4b475](https://github.com/myparcelnl/woocommerce/commit/4c4b47570d7d8c9a3e92c78dd028b52789b65cd8))
* order status change after label print in backoffice ([#831](https://github.com/myparcelnl/woocommerce/issues/831)) ([e7d8560](https://github.com/myparcelnl/woocommerce/commit/e7d856056588128dcb121cac11b6943308cb2f6e))


### Bug Fixes

* allow empty shipping method in post from checkout ([#834](https://github.com/myparcelnl/woocommerce/issues/834)) ([dc304d1](https://github.com/myparcelnl/woocommerce/commit/dc304d15f1434568ac7b5dae887ac55746685ed5))
* **export:** show correct shipment status in order grid ([#828](https://github.com/myparcelnl/woocommerce/issues/828)) ([c991c90](https://github.com/myparcelnl/woocommerce/commit/c991c909e42a4cb703be580edd4dc2529a88e93a)), closes [#809](https://github.com/myparcelnl/woocommerce/issues/809)
* no shipping details when using local pickup ([#826](https://github.com/myparcelnl/woocommerce/issues/826)) ([0e351c3](https://github.com/myparcelnl/woocommerce/commit/0e351c3f327fec421d6528b1b48c1d0e5523fd85))

## [4.11.0](https://github.com/myparcelnl/woocommerce/compare/v4.10.0...v4.11.0) (2022-04-19)


### Features

* enable order management setting ([#824](https://github.com/myparcelnl/woocommerce/issues/824)) ([d05d507](https://github.com/myparcelnl/woocommerce/commit/d05d507f1c90fb0e9caac6075c005d5bceb1acbd))
* show message when order management is enabled ([#815](https://github.com/myparcelnl/woocommerce/issues/815)) ([66be2e7](https://github.com/myparcelnl/woocommerce/commit/66be2e78dd4ef9ad6fce11908fbc8d18c528ccfc))


### Bug Fixes

* **export:** messages handling ([#809](https://github.com/myparcelnl/woocommerce/issues/809)) ([e073f55](https://github.com/myparcelnl/woocommerce/commit/e073f557c715588152df392a3462e9a65b459159))
* only show delivery date in order view when necessary ([#816](https://github.com/myparcelnl/woocommerce/issues/816)) ([44e02a7](https://github.com/myparcelnl/woocommerce/commit/44e02a73c326ad3f4c8e4d8978bd6bd60ad07792))
* revert only show delivery date in order view when necessary ([#816](https://github.com/myparcelnl/woocommerce/issues/816))([#820](https://github.com/myparcelnl/woocommerce/issues/820)) ([901316b](https://github.com/myparcelnl/woocommerce/commit/901316b96c94835431ee174a8b771104f9e4e009))
* syntax error on 4.1.0 migration ([#817](https://github.com/myparcelnl/woocommerce/issues/817)) ([df61016](https://github.com/myparcelnl/woocommerce/commit/df61016d9dd687f4ea49bcde93bcde441b2d4cc1))
* the weight calculations must be multiplied ([#814](https://github.com/myparcelnl/woocommerce/issues/814)) ([08c8992](https://github.com/myparcelnl/woocommerce/commit/08c899210239741195637a40020b794c202975a0))

## [4.10.0](https://github.com/myparcelnl/woocommerce/compare/v4.10.0-alpha.2...v4.10.0) (2022-03-21)


### Features

* add settings for same day delivery ([#810](https://github.com/myparcelnl/woocommerce/issues/810)) ([3c170b7](https://github.com/myparcelnl/woocommerce/commit/3c170b791d91105d237a23b4ccd2bad6931f19a0))


### Bug Fixes

* **checkout:** allow 6 characters as number suffix ([#806](https://github.com/myparcelnl/woocommerce/issues/806)) ([073acc9](https://github.com/myparcelnl/woocommerce/commit/073acc962347095e59f9af4019acf44106e73d83))
* fix fatal error in 4.1.0 migration ([#811](https://github.com/myparcelnl/woocommerce/issues/811)) ([ee20777](https://github.com/myparcelnl/woocommerce/commit/ee20777989fcf4f91074e6482cf69a1a8310e2bc))
* multiple return labels for label in the box ([#808](https://github.com/myparcelnl/woocommerce/issues/808)) ([fa19b4a](https://github.com/myparcelnl/woocommerce/commit/fa19b4a66340bab617981ab8725375fafd8a7c09))

## [4.10.0-alpha.2](https://github.com/myparcelnl/woocommerce/compare/v4.10.0-alpha.1...v4.10.0-alpha.2) (2022-02-25)


### Features

* check and handle shipment options with pickup ([#787](https://github.com/myparcelnl/woocommerce/issues/787)) ([93c402b](https://github.com/myparcelnl/woocommerce/commit/93c402bfd6c7bd638d5c23937b9147f2d9eb827f))


### Bug Fixes

* **checkout:** use correct package type ([#796](https://github.com/myparcelnl/woocommerce/issues/796)) ([ab27dd6](https://github.com/myparcelnl/woocommerce/commit/ab27dd61b29493cfaf0bf700c36c5be9406edecb))
* **instabox:** track trace not showing in notes ([#798](https://github.com/myparcelnl/woocommerce/issues/798)) ([3eb7c5b](https://github.com/myparcelnl/woocommerce/commit/3eb7c5bf1f58f69e99fed8ca0575dfb1e71e39ea))
* **om:** no errors for eu shipments without weight ([#804](https://github.com/myparcelnl/woocommerce/issues/804)) ([77258bb](https://github.com/myparcelnl/woocommerce/commit/77258bbc8d44989dd53932cba682cda5d380eab5))

## [4.10.0-alpha.1](https://github.com/myparcelnl/woocommerce/compare/v4.9.2...v4.10.0-alpha.1) (2022-02-16)


### Features

* **orders:** add dropoff point to order ([#770](https://github.com/myparcelnl/woocommerce/issues/770)) ([0e07f17](https://github.com/myparcelnl/woocommerce/commit/0e07f17df585d0ec83d4077993e0bd2b8d638eff))
* **orders:** show the export mode option ([#788](https://github.com/myparcelnl/woocommerce/issues/788)) ([46f7eea](https://github.com/myparcelnl/woocommerce/commit/46f7eeacb89b17161e177c4f91d230d746c7214b))


### Bug Fixes

* display ordergrid even when an address is faulty ([#772](https://github.com/myparcelnl/woocommerce/issues/772)) ([73150bb](https://github.com/myparcelnl/woocommerce/commit/73150bbb09c107345067b87e1117d1e2c4e7b385))
* **orders:** add weight when exporting orders ([#769](https://github.com/myparcelnl/woocommerce/issues/769)) ([918afbe](https://github.com/myparcelnl/woocommerce/commit/918afbe4a7ca2706ec627d5f8ae666564935faaa))

### [4.9.2](https://github.com/myparcelnl/woocommerce/compare/v4.9.1...v4.9.2) (2022-02-16)


### Bug Fixes

* catch fatal error on faulty address ([#799](https://github.com/myparcelnl/woocommerce/issues/799)) ([b5e0b66](https://github.com/myparcelnl/woocommerce/commit/b5e0b667c6fe882c296cbe3b08be1de1109ed2e3))

### [4.9.1](https://github.com/myparcelnl/woocommerce/compare/v4.9.0...v4.9.1) (2022-02-02)


### Bug Fixes

* **hotfix:** fix error when an address can't be split ([f9b3617](https://github.com/myparcelnl/woocommerce/commit/f9b3617060dbb66502b126cea50ae95ca61dcaaa)), closes [#781](https://github.com/myparcelnl/woocommerce/issues/781)

## [4.9.0](https://github.com/myparcelnl/woocommerce/compare/v4.8.2...v4.9.0) (2022-02-01)


### Features

* **instabox:** add same day delivery ([#764](https://github.com/myparcelnl/woocommerce/issues/764)) ([a5c1339](https://github.com/myparcelnl/woocommerce/commit/a5c13390f52f7effc5162d78a160eff1013a4026))
* persist notices over requests ([#753](https://github.com/myparcelnl/woocommerce/issues/753)) ([548160d](https://github.com/myparcelnl/woocommerce/commit/548160d00c83d9000cfbed8dea665691b9e9b9bf))
* propagate api url to delivery options ([#775](https://github.com/myparcelnl/woocommerce/issues/775)) ([9656be2](https://github.com/myparcelnl/woocommerce/commit/9656be262cd4f1a88b4c9f1a12bfe6c17980522f))


### Bug Fixes

* fix errors caused by deleted products ([#750](https://github.com/myparcelnl/woocommerce/issues/750)) ([ed37fb6](https://github.com/myparcelnl/woocommerce/commit/ed37fb6626288a0a85a55905d2b6848b5b00a7fc)), closes [#689](https://github.com/myparcelnl/woocommerce/issues/689)
* insurance amount showing when shipment type is not package ([#771](https://github.com/myparcelnl/woocommerce/issues/771)) ([d47a111](https://github.com/myparcelnl/woocommerce/commit/d47a111efca3841993e615e7bf28e9de7cc572dc))
* **pps:** fix export onlyRecipient and signature ([#743](https://github.com/myparcelnl/woocommerce/issues/743)) ([e1dfb04](https://github.com/myparcelnl/woocommerce/commit/e1dfb04e34802ea6f1347aab410e1690ee4f2673))
* **pps:** Return option is not possible for a combination with pickup ([a2ce328](https://github.com/myparcelnl/woocommerce/commit/a2ce3286c7c970f9b54b3eb153aa86d26357881f))
* **pps:** separate street from number when exporting order ([#756](https://github.com/myparcelnl/woocommerce/issues/756)) ([245be41](https://github.com/myparcelnl/woocommerce/commit/245be4119d027bd749b0c9440cb53af7e4a49245))
* **pps:** use price of single item instead of total price ([#755](https://github.com/myparcelnl/woocommerce/issues/755)) ([7296f6a](https://github.com/myparcelnl/woocommerce/commit/7296f6a1c4a5faea9cea9658f76591b3fda66497))

### [4.8.2](https://github.com/myparcelnl/woocommerce/compare/v4.8.1...v4.8.2) (2022-01-11)


### Bug Fixes

* **hotfix:** show delivery options prices in order review ([0712713](https://github.com/myparcelnl/woocommerce/commit/0712713b439a5169c1c76a98713ec7805aec727b))

### [4.8.1](https://github.com/myparcelnl/woocommerce/compare/v4.8.0...v4.8.1) (2022-01-10)


### Bug Fixes

* **hotfix:** prevent eternal recreation of webhook ([#758](https://github.com/myparcelnl/woocommerce/issues/758)) ([c8c68c4](https://github.com/myparcelnl/woocommerce/commit/c8c68c4d14bbee35fa5dc8ce9387b92851361f11))

## [4.8.0](https://github.com/myparcelnl/woocommerce/compare/v4.7.0...v4.8.0) (2022-01-03)


### Features

* add option to disable insurance for shipments to Belgium ([#744](https://github.com/myparcelnl/woocommerce/issues/744)) ([5017e19](https://github.com/myparcelnl/woocommerce/commit/5017e198f52111b69afd47950ee74c61c3a53aa0))
* add region to consignment when exporting ([#745](https://github.com/myparcelnl/woocommerce/issues/745)) ([0d91010](https://github.com/myparcelnl/woocommerce/commit/0d910100508637ae1fe550c8b46bf2e347023d4c))
* **pps:** export label description ([b7ae15f](https://github.com/myparcelnl/woocommerce/commit/b7ae15fc868d94b010ec89d9d81fcf9688ebba72))


### Bug Fixes

* accountsettings may be empty not absent ([#729](https://github.com/myparcelnl/woocommerce/issues/729)) ([004ce07](https://github.com/myparcelnl/woocommerce/commit/004ce07bdcf38ff1fe1587241542b43d6e1826cd))
* add 'parent' element to return_shipment_data array ([#738](https://github.com/myparcelnl/woocommerce/issues/738)) ([8dfb65f](https://github.com/myparcelnl/woocommerce/commit/8dfb65f442d33e88a585056c2449c50eb0cafea7))
* allow old shipping methods in order ([#720](https://github.com/myparcelnl/woocommerce/issues/720)) ([fda83bc](https://github.com/myparcelnl/woocommerce/commit/fda83bc9ca21760e822a55ecfd298218ab2bc9e5))
* export orders with a pickup location for PPS ([4b21dea](https://github.com/myparcelnl/woocommerce/commit/4b21dea08ee743fc77919ca36de0ab023cc1be7d))
* **pps:** export ROW orders ([#742](https://github.com/myparcelnl/woocommerce/issues/742)) ([48867a6](https://github.com/myparcelnl/woocommerce/commit/48867a641d4b6532a682609ecb4ddd6f598209ea))
* retour email not working ([#751](https://github.com/myparcelnl/woocommerce/issues/751)) ([4334bbf](https://github.com/myparcelnl/woocommerce/commit/4334bbff6f1fe42624dc7b94c379e39ae9e4de9c))
* save lowest insurance possibility ([#734](https://github.com/myparcelnl/woocommerce/issues/734)) ([c962a22](https://github.com/myparcelnl/woocommerce/commit/c962a223f401299a414faded7affd16b0a09f8b5)), closes [#730](https://github.com/myparcelnl/woocommerce/issues/730)

## [4.7.0](https://github.com/myparcelnl/woocommerce/compare/v4.6.0...v4.7.0) (2021-11-18)


### Features

* add redjepakketje ([#705](https://github.com/myparcelnl/woocommerce/issues/705)) ([aa1721d](https://github.com/myparcelnl/woocommerce/commit/aa1721d3f75d969c01024b18828f70fd02d81eab))


### Bug Fixes

* fix nonexistent delivery date in label description ([#725](https://github.com/myparcelnl/woocommerce/issues/725)) ([dabe5cf](https://github.com/myparcelnl/woocommerce/commit/dabe5cf1741f831ffe3a2a59e4a6b2b0a911d804))
* no notice when weight missing in order ([#721](https://github.com/myparcelnl/woocommerce/issues/721)) ([3dc4760](https://github.com/myparcelnl/woocommerce/commit/3dc4760d27a5abe2ebf552047406cccbd156a8fe))

## [4.6.0](https://github.com/myparcelnl/woocommerce/compare/v4.5.0...v4.6.0) (2021-11-05)


### Features

* customize delivery type title on confirmation ([#713](https://github.com/myparcelnl/woocommerce/issues/713)) ([df10806](https://github.com/myparcelnl/woocommerce/commit/df10806d63ef4e7bfba84b6bdc608a06d90caa7e))


### Bug Fixes

* delivery date display condition ([#703](https://github.com/myparcelnl/woocommerce/issues/703)) ([8c1ea56](https://github.com/myparcelnl/woocommerce/commit/8c1ea56d33f1e03cd47fe14fa0663a9c147fc193))
* Duplicate queries in wordpress backend ([#706](https://github.com/myparcelnl/woocommerce/issues/706)) ([54bf853](https://github.com/myparcelnl/woocommerce/commit/54bf8539eb2faa804839509e79846775918e9e23))
* open old orders where the data getExtraOptionsFromOrder is still a string ([8566784](https://github.com/myparcelnl/woocommerce/commit/8566784fa7cb4d0e49091bb2d81ee1a130ded067))
* use correct product quantity for label description ([5f760d0](https://github.com/myparcelnl/woocommerce/commit/5f760d01cb75bcf1b2f2e673813e945fc1099220))

## [4.5.0](https://github.com/myparcelnl/woocommerce/compare/v4.4.5...v4.5.0) (2021-10-08)


### Features

* Added order mode 'Export entire order' aka PPS. ([#661](https://github.com/myparcelnl/woocommerce/issues/661)) ([a00c8d8](https://github.com/myparcelnl/woocommerce/commit/a00c8d8e05c66369840d1547a6b13b55d94cc533))
* Always show delivery date when available on ordergrid ([0386112](https://github.com/myparcelnl/woocommerce/commit/0386112c23b944ee6acf4d42c15d360db0512b51))
* disable morning/evening delivery when agecheck is present ([#699](https://github.com/myparcelnl/woocommerce/issues/699)) ([625ccd7](https://github.com/myparcelnl/woocommerce/commit/625ccd709586ed1e9a3c61ea73fe16eb6ecb49dd))
* make age check, large format and insurance available to pickup delivery ([1beda77](https://github.com/myparcelnl/woocommerce/commit/1beda77887bdd48178d834aad92ad6661dcbd3e2))


### Bug Fixes

* add wpdesk flexible shipping single compatibility ([1e3092f](https://github.com/myparcelnl/woocommerce/commit/1e3092f1809ee90988ee08cfa508cd6fd2693ebe))
* return extra options without extra linebreaks ([#693](https://github.com/myparcelnl/woocommerce/issues/693)) ([20d241e](https://github.com/myparcelnl/woocommerce/commit/20d241eb5a4cd1443bb512610c581fb6206fa020))
* url for print button to act like other print label buttons ([#698](https://github.com/myparcelnl/woocommerce/issues/698)) ([42f5b19](https://github.com/myparcelnl/woocommerce/commit/42f5b19d67d16b500f0ba70acadc0834bc9ec6be))

### [4.4.5](https://github.com/myparcelnl/woocommerce/compare/v4.4.4...v4.4.5) (2021-08-17)


### Features

* add empty digital stamp weight setting ([9739d95](https://github.com/myparcelnl/woocommerce/commit/9739d950ccab7d47f7aedf5f03d2799255c8f93c))
* filter orders by delivery date in order overview ([f39fa2b](https://github.com/myparcelnl/woocommerce/commit/f39fa2b2cd32b09b576da370a8080510ea7fa242))
* update package type above shipment options dialog without refreshing page ([db90a82](https://github.com/myparcelnl/woocommerce/commit/db90a82e8f33cad616d402d2527bcf86a785e9a7))


### Bug Fixes

* age check with different scenarios ([3b9dc8b](https://github.com/myparcelnl/woocommerce/commit/3b9dc8b49f0d183f0cf659d6f3c427f257ec530d))
* Changelog and update version number ([00c02de](https://github.com/myparcelnl/woocommerce/commit/00c02de7d9dad6cf61ddb18eca779391079cb18e))
* Changelog for version 4.4.1 ([bc942bb](https://github.com/myparcelnl/woocommerce/commit/bc942bb4b97f7fc1d686186954eb5853e34425c8))
* check shipment type before empty DPZ is calculated ([1b399bf](https://github.com/myparcelnl/woocommerce/commit/1b399bf0a344439b80a7274a321fac11c25f7d07))
* double export when using the bulk actions 2 ([2f21da0](https://github.com/myparcelnl/woocommerce/commit/2f21da041e7d75ed7a3722d7a4df7eb6ad9b1395))
* export age with different scenarios ([6e2a4f4](https://github.com/myparcelnl/woocommerce/commit/6e2a4f4253298db44b782e1450e9bc3d8b095ee7))
* ignore virtual products when creating consignment ([9c69528](https://github.com/myparcelnl/woocommerce/commit/9c69528ceea4d4294a5e61f58878fd8db394e06e))
* indenting for class-wcmp-frontend.php ([105a154](https://github.com/myparcelnl/woocommerce/commit/105a154239ea81b4122a2e04eb6608eb9fc687b9))
* object type is only supported from php 7.2 ([1a8bda3](https://github.com/myparcelnl/woocommerce/commit/1a8bda3e2193d0a22c02b65c2c10985324f51efd))
* revert code for age check ([32178cf](https://github.com/myparcelnl/woocommerce/commit/32178cffb471126acc9beb3d90305de1c7ce81e7))
* update changelog ([0d5bef8](https://github.com/myparcelnl/woocommerce/commit/0d5bef8e2309d607f5415c20e1475af634f3b351))
* update version to 4.4.2 ([565442f](https://github.com/myparcelnl/woocommerce/commit/565442ff09435e0b4d4bf45a5a00764402c72f69))
* when a product is created, country of origin must be set to default ([3e16078](https://github.com/myparcelnl/woocommerce/commit/3e1607882e2d6925c81d4fd074dc2bc90feea8d8))
* woocommerce hooks for the address fields in the admin ([46c7e62](https://github.com/myparcelnl/woocommerce/commit/46c7e62fe6a65d92c027a8637b33c7b57b978fc2))

### [4.4.4](https://github.com/myparcelnl/woocommerce/compare/v4.4.3...v4.4.4) (2021-07-15)


### Bug Fixes

* changelog for version 4.4.4 ([9016d3b](https://github.com/myparcelnl/woocommerce/commit/9016d3bc63a940ef4d8a6823343d979dd35fa591))
* minimale steps for weight ([d6290e5](https://github.com/myparcelnl/woocommerce/commit/d6290e526c0d80b3a0884474d03fd607d07d3be0))
* return correct country of origin ([7ddd6a1](https://github.com/myparcelnl/woocommerce/commit/7ddd6a1eaa391e264a09d3a835bba7ca1880ea0f))

### [4.4.3](https://github.com/myparcelnl/woocommerce/compare/v4.4.2...v4.4.3) (2021-07-14)


### Bug Fixes

* update changelog for 4.4.3 ([4140911](https://github.com/myparcelnl/woocommerce/commit/41409113594e74f1d1927d5b874184afef1a93aa))
* update version number to 4.4.3 ([967d01d](https://github.com/myparcelnl/woocommerce/commit/967d01daec5bd7f973ca5201f5617bb583455ab5))

### [4.4.2](https://github.com/myparcelnl/woocommerce/compare/v4.4.1...v4.4.2) (2021-07-13)


### Features

* add empty digital stamp weight setting ([a93f191](https://github.com/myparcelnl/woocommerce/commit/a93f191de7ed6d362bdf93b1965a28cb58e53a19))
* filter orders by delivery date in order overview ([2161306](https://github.com/myparcelnl/woocommerce/commit/21613065f623c18c89028a294944735c1fa0aa51))


### Bug Fixes

* age check with different scenarios ([c69035f](https://github.com/myparcelnl/woocommerce/commit/c69035fbd5474f949a0c0fd9192ad5ce915d69fd))
* Changelog for version 4.4.1 ([da0e191](https://github.com/myparcelnl/woocommerce/commit/da0e1919f1dd941320fb3c76cb9e0a4928fc6768))
* double export when using the bulk actions 2 ([5b8b526](https://github.com/myparcelnl/woocommerce/commit/5b8b526d55d9bf68b8ead5a52c9a01a6e3babe23))
* export age with different scenarios ([f2ef1ce](https://github.com/myparcelnl/woocommerce/commit/f2ef1ceefe2e17db870d9ace64a828227bc3a672))
* revert code for age check ([2096078](https://github.com/myparcelnl/woocommerce/commit/2096078b7c7a9243d378af39c6345b1d83739c1e))
* update changelog ([bd24b57](https://github.com/myparcelnl/woocommerce/commit/bd24b579f537ab22e45b8581dfe90f21722b490c))
* update version to 4.4.2 ([b0a0627](https://github.com/myparcelnl/woocommerce/commit/b0a0627cfc1a021f7c5e545fe183887792974488))

### [4.4.1](https://github.com/myparcelnl/woocommerce/compare/v4.4.0...v4.4.1) (2021-07-02)


### Features

* added setting to show or hide delivery date in frontend ([#655](https://github.com/myparcelnl/woocommerce/issues/655)) ([8f8199c](https://github.com/myparcelnl/woocommerce/commit/8f8199c58c7a34f05bee251e9df9c18c1b8b493c))


### Bug Fixes

* additional fees not being added to totals if delivery options titles are empty ([8419061](https://github.com/myparcelnl/woocommerce/commit/8419061921cac5c580e6e7e965c554ba7b86db13)), closes [#648](https://github.com/myparcelnl/woocommerce/issues/648)
* casting packageType to a string ([3405b3a](https://github.com/myparcelnl/woocommerce/commit/3405b3a4cdfc9d4cf1935523f340e8c1ca4b27f4))
* error on changing country on account page ([1eff1a9](https://github.com/myparcelnl/woocommerce/commit/1eff1a952962ef449dff022753557cb50982d9b0)), closes [#644](https://github.com/myparcelnl/woocommerce/issues/644)
* error translation why an order cannot be exported. ([12670c5](https://github.com/myparcelnl/woocommerce/commit/12670c51875865c5e65d5eec8c62f3910c34608d))
* extra check if package type is set in _myparcel_shipment_options meta ([2b14dce](https://github.com/myparcelnl/woocommerce/commit/2b14dceb218f4f7cec4d72f6670dbf1a49c33389))

## [4.4.0](https://github.com/myparcelnl/woocommerce/compare/v4.3.2...v4.4.0) (2021-05-26)


### Features

* added setting pickup default view map or list ([713299a](https://github.com/myparcelnl/woocommerce/commit/713299aca1e72448c6c49b07d8dd229553d2b972))
* added setting pickup default view map or list ([b9719fa](https://github.com/myparcelnl/woocommerce/commit/b9719fa919b12afd38107dfa5df7e7e151d70d4b))
* added setting pickup default view map or list, addressed requested changes ([2df1491](https://github.com/myparcelnl/woocommerce/commit/2df1491727371f2af29655cdf66d6ad35ec238b3))
* choose an order status that will automatically export shipment(… ([#640](https://github.com/myparcelnl/woocommerce/issues/640)) ([9b580d0](https://github.com/myparcelnl/woocommerce/commit/9b580d0449fc50f452b9985f989df929d76f67d4))
* Display product country of origin as list, default settings cou… ([#625](https://github.com/myparcelnl/woocommerce/issues/625)) ([6f39e10](https://github.com/myparcelnl/woocommerce/commit/6f39e101c93798d975d4ebdc85943d67b247c96a))
* only show delivery options with non-virtual product in order ([#646](https://github.com/myparcelnl/woocommerce/issues/646)) ([698733b](https://github.com/myparcelnl/woocommerce/commit/698733b4f44c04858ae49072d500bf834b55e828))


### Bug Fixes

* alignment of constants collo_amount ([80fa6a6](https://github.com/myparcelnl/woocommerce/commit/80fa6a608ecaae50951eb9e2ff9c2f04e1f4de67))
* error when changing order while shipment_options_extra meta does not exist ([db13b3a](https://github.com/myparcelnl/woocommerce/commit/db13b3a97c37f99ce64dee61af59c630616d5401))
* errors about empty shipping class ([3a159d6](https://github.com/myparcelnl/woocommerce/commit/3a159d66c5d309823f9d7ec2e0f9f0d1ea3fb20a))
* order of the if statement ([0fdc80f](https://github.com/myparcelnl/woocommerce/commit/0fdc80f7a9b9d5564424150cfbd0c95dad278c5e))
* release notes for version 4.4.0 ([7fad94e](https://github.com/myparcelnl/woocommerce/commit/7fad94e3855d10ae99327373ab2d742dee34d4af))
* set-mailbox-shipments-outside-NL-to-Package ([#642](https://github.com/myparcelnl/woocommerce/issues/642)) ([7243a2b](https://github.com/myparcelnl/woocommerce/commit/7243a2b2e88e010156c11067f1c38fb1b7a9e302))
* use type hidden to hide the delivery options input. this will solve theme problems. ([c3b73ff](https://github.com/myparcelnl/woocommerce/commit/c3b73ffbd6394d9fd32bf66c5b695089b9dafbff)), closes [#623](https://github.com/myparcelnl/woocommerce/issues/623)

### [4.3.2](https://github.com/myparcelnl/woocommerce/compare/v4.3.0...v4.3.2) (2021-03-29)


### Features

* option change order status after export or after printing ([424a99a](https://github.com/myparcelnl/woocommerce/commit/424a99af1ee047865cb8b782c5064baad1b413fd))


### Bug Fixes

* add wpm-config.json inside the zip file ([74872c3](https://github.com/myparcelnl/woocommerce/commit/74872c33a96d9b96f074e5b3b69124fddac1d54b))
* clarify variable names when they change order status ([c1568e9](https://github.com/myparcelnl/woocommerce/commit/c1568e9250475bb541ebad852e8dc49131a55caf))
* fatal error when getting meta from products ([c6f293c](https://github.com/myparcelnl/woocommerce/commit/c6f293c25ec803dfd9461575bebd35c869ee0b19))
* insurance is not properly saved. ([e8e5e4c](https://github.com/myparcelnl/woocommerce/commit/e8e5e4c8bfbee170ef16b1a452f38c2fcd322b99))
* show full delivery options for {{myparcel_delivery_options}} ([956cbc8](https://github.com/myparcelnl/woocommerce/commit/956cbc8c81d6b2be681e432648a1e4d758c9d66e))
* translation for changing order status after ([1b99150](https://github.com/myparcelnl/woocommerce/commit/1b99150cc73218b0b9486a195206a5f20c84f801))
* typo in changelog ([9c99f9f](https://github.com/myparcelnl/woocommerce/commit/9c99f9fe7cbd69e38101d6a98f85046dfd60a23b))
* update changelog and vimeo promo movie ([d35d45e](https://github.com/myparcelnl/woocommerce/commit/d35d45efbd790890004a9564a678c9f4c6de8148))
* update version to 4.3.1 ([fbd7f96](https://github.com/myparcelnl/woocommerce/commit/fbd7f962cdbd6e05865233f4b89bc8d67ecc0f2d))
* url for old vendor map ([c5703dc](https://github.com/myparcelnl/woocommerce/commit/c5703dc7067a57ce838090f2b1ea1d420acba47f))
* use const for insurance possibilities ([4cac5f1](https://github.com/myparcelnl/woocommerce/commit/4cac5f106f2dc532626324f20e1875335668ff29))

## [4.3.0](https://github.com/myparcelnl/woocommerce/compare/v4.2.1...v4.3.0) (2021-03-19)


### Features

* **compatibility:** support wp desk flexible shipping ([d241d0a](https://github.com/myparcelnl/woocommerce/commit/d241d0a817e828aa906beda54baf0391430dfdef))
* option for total price/surcharge price ([8825c19](https://github.com/myparcelnl/woocommerce/commit/8825c19853ef59e739cd8c51a2ecc5e01acbd571))


### Bug Fixes

* .mo files not being generated properly ([220e3f4](https://github.com/myparcelnl/woocommerce/commit/220e3f44c562fa6f2513f4e7fdefd6e3f4fb9546))
* 4.2.1 migration being able to make empty parcel weight too small ([517133a](https://github.com/myparcelnl/woocommerce/commit/517133a84a72f06204bcc5d6b9196de178f63c84))
* base price for signature and only_recipient ([ebd2c06](https://github.com/myparcelnl/woocommerce/commit/ebd2c068fd570526708a5e8a10440fb57c4bb853))
* Call to undefined function isEuCountry() ([7135153](https://github.com/myparcelnl/woocommerce/commit/713515382f48cc45bf7bc799acfe701ab91462e3))
* **checkout:** show correct prices when switching between shipping methods ([#580](https://github.com/myparcelnl/woocommerce/issues/580)) ([f140d39](https://github.com/myparcelnl/woocommerce/commit/f140d3926eefe6ab2744d3cf996578c43915a169)), closes [#557](https://github.com/myparcelnl/woocommerce/issues/557)
* handle weight in a more robust manner ([66db74b](https://github.com/myparcelnl/woocommerce/commit/66db74b6c1b970d5f89633a47466c8c7c620dfec))
* MyParcel manual url ([49bc34f](https://github.com/myparcelnl/woocommerce/commit/49bc34fcea607dfe0f7154c031da487cf8d92ee5))
* price format translation file and align code ([29fa084](https://github.com/myparcelnl/woocommerce/commit/29fa0842756d7516ac683f9b5471b5696c3dae9f))
* release notes for version 4.3.0 ([56ae830](https://github.com/myparcelnl/woocommerce/commit/56ae8305b511bf521e1785ebeca6728a63dc8b05))
* set the country id in one line ([89bce0c](https://github.com/myparcelnl/woocommerce/commit/89bce0c5c25134a1456ef51d4022c1833e7265c3))
* show correct package type in order grid ([903abc6](https://github.com/myparcelnl/woocommerce/commit/903abc61fef7d6a8eedbae7960315ebfa7f0976a))
* show price surcharge in the myparcel config ([af90b40](https://github.com/myparcelnl/woocommerce/commit/af90b4099f80f0cb62d36b75c4934e150d20b945))
* use country codes from SDK ([3612766](https://github.com/myparcelnl/woocommerce/commit/3612766a035908c6b90d092edfb56c61f5f8c313))
* use key for `Show prices as` translations ([bf1e008](https://github.com/myparcelnl/woocommerce/commit/bf1e00854435f3e765e06da682b5d45e52541bc0))
* weight error on exporting digital stamp ([554a50b](https://github.com/myparcelnl/woocommerce/commit/554a50bbd18db24ba612cfd9042f46221c710076))

### [4.2.1](https://github.com/myparcelnl/woocommerce/compare/v4.2.0...v4.2.1) (2021-02-22)


### Features

* add taxes to delivery options prices when needed ([#429](https://github.com/myparcelnl/woocommerce/issues/429)) ([d065acd](https://github.com/myparcelnl/woocommerce/commit/d065acdfa5323f28970671655a77b11a4151d984))
* import translations from google sheets ([962edc7](https://github.com/myparcelnl/woocommerce/commit/962edc7c1bdf742285d6386cbf7f53ddbbaab1de))
* remove serialization of meta data ([81067bd](https://github.com/myparcelnl/woocommerce/commit/81067bd9565e169f4158ba717fe1d5bbb76f5711))
* spread order weight across shipments for multicollo shipments ([294ad5e](https://github.com/myparcelnl/woocommerce/commit/294ad5e06f6302661dab5ff93b8b8e735fff463b))


### Bug Fixes

* age check for shipment options ([6a43302](https://github.com/myparcelnl/woocommerce/commit/6a4330235a2c4d830d3d628f5e8b84efd0d7de38))
* barcode in email with order status automation ([d94c7c9](https://github.com/myparcelnl/woocommerce/commit/d94c7c9832c58af1db8825fa26db7f8511c93ace))
* calculate weight from grams to kilos during the migration ([9cef974](https://github.com/myparcelnl/woocommerce/commit/9cef974799d27dd120aa4e57aeb2f67a24b4eec0))
* camelcase and weak equality ([7a019fb](https://github.com/myparcelnl/woocommerce/commit/7a019fb6240de47c5e18bd30f6f745cc4d47f8fa))
* check if correct package type is used for destination ([e618d2e](https://github.com/myparcelnl/woocommerce/commit/e618d2e374172115a8537df2ae205a02912a581e))
* clarification of the replaceValue method ([e06d671](https://github.com/myparcelnl/woocommerce/commit/e06d6719ea21e4cc11fe7609219ba4ddc5aca0dc))
* don't use property for weightUnit ([62b28c8](https://github.com/myparcelnl/woocommerce/commit/62b28c87bda2945c9e1cb23b5494839382612caa))
* error on php 7.1 because of use of unsupported const ([7165d27](https://github.com/myparcelnl/woocommerce/commit/7165d277864bcbc3628cf6c96d5b277c4d7bc9dd))
* error when forcing billing address as shipping address ([0ab5ccc](https://github.com/myparcelnl/woocommerce/commit/0ab5ccc165df1771ca0a1ab20952faa22849d7c8))
* export age check with setting of product ([4f4bbb0](https://github.com/myparcelnl/woocommerce/commit/4f4bbb0b90a89f4fc2f7fd9590bbb93349bd9cec))
* export order with incorrect shipment ([850b8e6](https://github.com/myparcelnl/woocommerce/commit/850b8e6d77b780a02cf2845add23f937c992a9c4))
* name of Age check option ([af7c76d](https://github.com/myparcelnl/woocommerce/commit/af7c76d8b1fbbf33891b56d606587e45e099952d))
* printing from offset dialog opens new tab with correct url ([18d90fd](https://github.com/myparcelnl/woocommerce/commit/18d90fd3eebb78a8d89f25b86e7c49eed794d358))
* remove SDK in includes folder ([2b59575](https://github.com/myparcelnl/woocommerce/commit/2b595756ead8a41792efcf4c55e6e9955ada7cc0))
* set myparcel response cookie with 20 sec expire ([ea6b959](https://github.com/myparcelnl/woocommerce/commit/ea6b9598a93bbe3c73c0b85598f29fe4cb5ce500))
* unnecessary blank lines ([5c9664d](https://github.com/myparcelnl/woocommerce/commit/5c9664d14f2c9cec423dd4d7d44d18b292ddcb39))
* update delivery options and SDK ([cd01435](https://github.com/myparcelnl/woocommerce/commit/cd014354e90df8064119c682a29d4c846ec2f4fe))
* url for old vendor map ([#558](https://github.com/myparcelnl/woocommerce/issues/558)) ([2972058](https://github.com/myparcelnl/woocommerce/commit/2972058ec2774df1d1e42f4c3caac1f4c47a73b0))
* use const for the cookie expire time ([8dd639b](https://github.com/myparcelnl/woocommerce/commit/8dd639b26846f3c55a117671966a4ffe8738c660))
* use isset for error reporting ([ca560d4](https://github.com/myparcelnl/woocommerce/commit/ca560d43e73572c6ea2ddd719a45805f5855dbbd))
* use saturday cutoff time ([53095c8](https://github.com/myparcelnl/woocommerce/commit/53095c82996d82f4aff3df6d3e51d4858ccc8385))

## [4.2.0](https://github.com/myparcelnl/woocommerce/compare/v4.0.2...v4.2.0) (2021-01-21)


### Features

* improve shipment options dialog ([f15c58d](https://github.com/myparcelnl/woocommerce/commit/f15c58d52c3766b3f6f75980dcf619967f32577d)), closes [#397](https://github.com/myparcelnl/woocommerce/issues/397) [#404](https://github.com/myparcelnl/woocommerce/issues/404)


### Bug Fixes

* add 4.1.0 settings migration ([e6ddaa3](https://github.com/myparcelnl/woocommerce/commit/e6ddaa3895dcf460a509729287fd3079e44f2bc8))
* add status for letter and dpz and show them on the barcode column ([6b16d4b](https://github.com/myparcelnl/woocommerce/commit/6b16d4bba917b036de8de5cb38c02c2358a88705))
* age check must be a bool and fix translations ([69a79b9](https://github.com/myparcelnl/woocommerce/commit/69a79b9d4a769683340570225f007ce3ccda7cc1))
* array setting conditions not being checked properly ([8383f97](https://github.com/myparcelnl/woocommerce/commit/8383f97c8e3047725de2c5b720bbbd6fe070ecb3))
* change MyParcel shipment to Delivery information ([85bb450](https://github.com/myparcelnl/woocommerce/commit/85bb4504c7426b7e669c0757fa3df951226838d1))
* conditions attribute being rendered alongside data-conditions ([70c7efd](https://github.com/myparcelnl/woocommerce/commit/70c7efd521eff795358ef2333e845d3794737511))
* crack down on invalid package types ([4c9a8be](https://github.com/myparcelnl/woocommerce/commit/4c9a8beb1e6b0138403d43b7d7c7ed4998ef9657))
* delivery options returning 'null' ([807a64a](https://github.com/myparcelnl/woocommerce/commit/807a64a7be5c2a893e1627e63a3f20b54e2fc008))
* deliveryOptionsFromPost is not empty has string with null in it ([459c73d](https://github.com/myparcelnl/woocommerce/commit/459c73daabc6dfb72557a65eef5b3db3fca427e8))
* digital stamp select in shipment options filled correctly ([b37b29d](https://github.com/myparcelnl/woocommerce/commit/b37b29d13707dfebe2fc0d305fcda949851e32ff))
* do not save address in address book and update min php version ([a8a6d2a](https://github.com/myparcelnl/woocommerce/commit/a8a6d2a5407c6dc943527375c8edd2080f1bf487))
* don't close shipment options on clicking a tiptip ([0df1532](https://github.com/myparcelnl/woocommerce/commit/0df1532539e9136e23a5183afe503e9726740cb1))
* don't load checkout scripts on order received page ([7342746](https://github.com/myparcelnl/woocommerce/commit/7342746c830aabf471167760f706f545576aa496))
* double code for track trace in my account ([2fe2d96](https://github.com/myparcelnl/woocommerce/commit/2fe2d96f9ab28db10f75470e1c5c8b6df0e54438))
* empty parcel weight ([8392278](https://github.com/myparcelnl/woocommerce/commit/8392278c713944ca96cc90743e8523dae826a932))
* error messages for international shipment [#73](https://github.com/myparcelnl/woocommerce/issues/73) ([b6de996](https://github.com/myparcelnl/woocommerce/commit/b6de9960cc77f58475537d1386753179f40de442))
* error on checkout when using custom address fields ([0908d8b](https://github.com/myparcelnl/woocommerce/commit/0908d8b46730eb9df426052dda957aeeeac6d057))
* error on loading settings page ([43fafd4](https://github.com/myparcelnl/woocommerce/commit/43fafd4180350135e9759ab4e2f7edc75bb7ca3c))
* Failed opening class-wcmypa-settings.php ([f54e5c6](https://github.com/myparcelnl/woocommerce/commit/f54e5c639d665036ebc3c7ae9ace73222468e37c))
* hide Print return label directly ([ad57f51](https://github.com/myparcelnl/woocommerce/commit/ad57f515935821b4326e6c002a83589312c48f20))
* hide Print return label directly ([547f702](https://github.com/myparcelnl/woocommerce/commit/547f702e0854c93199f1422cfb77afe60e4e1ace))
* indents ([b5371ed](https://github.com/myparcelnl/woocommerce/commit/b5371ed32dab0e4e3da08571eb3d64038d0fb903))
* indents ([a9695a0](https://github.com/myparcelnl/woocommerce/commit/a9695a085a7c565fb4f082ac274ae896d5fb8736))
* make default export settings show up in shipment options ([6b289fb](https://github.com/myparcelnl/woocommerce/commit/6b289fb7000394291062b9698bd3977572c6fce8))
* make dependencies properly handle multiple conditions on the same element ([ee4cbf6](https://github.com/myparcelnl/woocommerce/commit/ee4cbf606c1d65cf12dda1b43b31d4a19b302536))
* make the comment more specific that dpz is also not possible for ROW ([e2fb070](https://github.com/myparcelnl/woocommerce/commit/e2fb070911f6fab8c1cfada259d8840edb168761))
* only add empty parcel weight to parcels ([af883ad](https://github.com/myparcelnl/woocommerce/commit/af883ad72344b779a58dbf48fef3ead7c7e19313))
* open a label in new tab, if label position is activated ([818bd07](https://github.com/myparcelnl/woocommerce/commit/818bd073b5e3ada6908311049efe7f20e44afcce))
* package type from shipping method/class not shown in order grid ([0f764d6](https://github.com/myparcelnl/woocommerce/commit/0f764d692d9fc14c760d1798d6a5d20c9cbc9c3e))
* properly add/remove cart fees when using delivery options ([ea40af4](https://github.com/myparcelnl/woocommerce/commit/ea40af4772e5ab25e944eda5081e43424fa42acd))
* re-enable large format option for non-home country orders ([6080d59](https://github.com/myparcelnl/woocommerce/commit/6080d596bb1c343558017588234cb5705fbde95a))
* remove digital stamp for ROW shipment ([37b1e94](https://github.com/myparcelnl/woocommerce/commit/37b1e94c5f605783a9872b1fc886a08634c07b5f))
* remove unused functions, rename template and show proper weight in return form ([98d69b0](https://github.com/myparcelnl/woocommerce/commit/98d69b0701a023d021f4643c7cbea06531f21ee5))
* scoping issues ([59c7306](https://github.com/myparcelnl/woocommerce/commit/59c7306c7676115b59dae036f448a8822f4afcea))
* send return email modal ([#543](https://github.com/myparcelnl/woocommerce/issues/543)) ([3e64a78](https://github.com/myparcelnl/woocommerce/commit/3e64a7898b8fb19435f91ff4bdc188f32cd12888))
* shipment options not showing correctly initially ([0b5e3ce](https://github.com/myparcelnl/woocommerce/commit/0b5e3ce8e804f6dd3603d45c6f947ce4a7bc6f03))
* show correct delivery type in orders grid ([3567258](https://github.com/myparcelnl/woocommerce/commit/3567258b109eda7dc99ad7eba1ac5e6b55c1236b))
* show delivery date in order grid for any order that has one ([68ab175](https://github.com/myparcelnl/woocommerce/commit/68ab1758b0ad9b90dbd6a656100b5d5cdf114a04))
* some settings still not migrated correctly ([24a6f09](https://github.com/myparcelnl/woocommerce/commit/24a6f0983b0f6158cd97db17c24fb7179453b534))
* spinner for order grid bulk actions ([9cceff6](https://github.com/myparcelnl/woocommerce/commit/9cceff67b06f92c7c7d67bf1d7462eb280e8a81f))
* support receiving falsy values from get_option in migrations to fix fatal errors ([237923f](https://github.com/myparcelnl/woocommerce/commit/237923f070a5e78e23646f880595325bc16bc07b))
* track & trace in email on automatic status change ([e19402e](https://github.com/myparcelnl/woocommerce/commit/e19402e6517780c414b9dd95d8d0132cf8a3d4b4))
* translations for dpz and letter status ([3fb2107](https://github.com/myparcelnl/woocommerce/commit/3fb21075cacdc75c948ed8d9855bfac0f77ed2f3))
* unable to send return email ([4f89fe2](https://github.com/myparcelnl/woocommerce/commit/4f89fe286384cf0013fb0ed94eef29e040a22fa4))
* Uncaught Error WCMP_Country_Codes ([ecef985](https://github.com/myparcelnl/woocommerce/commit/ecef98579d540e7286a197f804670d6433b0c27d))
* update translations ([b9fb1c1](https://github.com/myparcelnl/woocommerce/commit/b9fb1c19032419c69489522f97ffe4d72a4dac01))
* update version and changelog ([cd6cbe7](https://github.com/myparcelnl/woocommerce/commit/cd6cbe7ec89c30abf9211e7e2094dd000e4676bf))
* use constants for minimum PHP version ([45b80d3](https://github.com/myparcelnl/woocommerce/commit/45b80d3a616a4776707c94b00906904a0262d66e))
* use correct user agent function ([b66d4c1](https://github.com/myparcelnl/woocommerce/commit/b66d4c15d2e00e27bf7d5a12e9d8f872757d7084))
* use declarations ans set status into an array ([0ea41a7](https://github.com/myparcelnl/woocommerce/commit/0ea41a7b6b4cb1515a7367cc4f4d55e665983278))
* use float for empty parcel weight ([b547a34](https://github.com/myparcelnl/woocommerce/commit/b547a3444f3e5448f0350e105830c42d840d579d))
* use the shippingMethod to get a class id ([13023a9](https://github.com/myparcelnl/woocommerce/commit/13023a9922112e6c33f5a802135c7afcc657f42f))


### Reverts

* Revert "update SDK" ([6d2bb7c](https://github.com/myparcelnl/woocommerce/commit/6d2bb7cd9157e87789bc9822c56b801b68674650))

### [4.0.2](https://github.com/myparcelnl/woocommerce/compare/v4.0.1...v4.0.2) (2020-09-14)

### [4.0.1](https://github.com/myparcelnl/woocommerce/compare/v4.0.0...v4.0.1) (2020-07-30)


### Reverts

* Revert "Update class-wcmp-export.php" ([998948b](https://github.com/myparcelnl/woocommerce/commit/998948b647e6a56d71651347607477d3d7e35eb2))
* Revert "solved conflicts" ([dced857](https://github.com/myparcelnl/woocommerce/commit/dced857fd28e7003d8660234b0d290c952b052bc))
* Revert "update version" ([cfff6dc](https://github.com/myparcelnl/woocommerce/commit/cfff6dc10c0d021eb78ce6edc8a9f7e794cf608a))
* Revert "Update html-bulk-options-form.php" ([6a4702b](https://github.com/myparcelnl/woocommerce/commit/6a4702b9100637d33115109c1934575ab0e3b553))
* Revert "Change BE to NL" ([c45ce1f](https://github.com/myparcelnl/woocommerce/commit/c45ce1f1bbad5a337ab839fca6c9b4857398523b))

### [3.1.7](https://github.com/myparcelnl/woocommerce/compare/v3.1.6...v3.1.7) (2019-07-16)

### [3.1.6](https://github.com/myparcelnl/woocommerce/compare/v3.1.5...v3.1.6) (2019-07-03)

### [3.1.5](https://github.com/myparcelnl/woocommerce/compare/v3.1.4...v3.1.5) (2019-05-14)

### [3.1.3](https://github.com/myparcelnl/woocommerce/compare/v3.1.2...v3.1.3) (2019-02-25)

### [3.1.2](https://github.com/myparcelnl/woocommerce/compare/v3.1.1...v3.1.2) (2019-02-19)

### [3.1.1](https://github.com/myparcelnl/woocommerce/compare/v3.1.0...v3.1.1) (2019-01-30)

### [3.0.10](https://github.com/myparcelnl/woocommerce/compare/v3.0.9...v3.0.10) (2018-12-05)

### [3.0.9](https://github.com/myparcelnl/woocommerce/compare/v3.0.8...v3.0.9) (2018-12-04)

### [3.0.8](https://github.com/myparcelnl/woocommerce/compare/v3.0.7...v3.0.8) (2018-11-30)

### [3.0.3](https://github.com/myparcelnl/woocommerce/compare/v3.0.2...v3.0.3) (2018-10-17)

### [3.0.2](https://github.com/myparcelnl/woocommerce/compare/v3.0.0...v3.0.2) (2018-10-09)

## [3.0.0-beta.3](https://github.com/myparcelnl/woocommerce/compare/v3.0.0-beta.2...v3.0.0-beta.3) (2018-10-03)


### Bug Fixes

* Notice: Undefined index: ([31fbcda](https://github.com/myparcelnl/woocommerce/commit/31fbcda49edda08e461db4c188c557a834f0d21f))

### [2.4.14](https://github.com/myparcelnl/woocommerce/compare/v2.4.13...v2.4.14) (2018-08-03)


### Bug Fixes

* if there is one shipping method is selected ([2284356](https://github.com/myparcelnl/woocommerce/commit/22843566dde718b9bfeff84a21114c1cbd9bc11a))

### [2.4.12](https://github.com/myparcelnl/woocommerce/compare/v2.4.11...v2.4.12) (2018-07-10)

### [2.4.10](https://github.com/myparcelnl/woocommerce/compare/v2.4.9...v2.4.10) (2018-04-26)

### [2.4.11](https://github.com/myparcelnl/woocommerce/compare/v2.4.10...v2.4.11) (2018-04-30)

### [2.4.10](https://github.com/myparcelnl/woocommerce/compare/v2.4.9...v2.4.10) (2018-04-26)

### [2.4.9](https://github.com/myparcelnl/woocommerce/compare/v2.4.8...v2.4.9) (2018-04-03)


### Bug Fixes

* dont refrash page when there is a option changed inside the order view ([2dee5cf](https://github.com/myparcelnl/woocommerce/commit/2dee5cf1a694a12b9134f4570f6e6fc3511bd86b))

### [2.4.8](https://github.com/myparcelnl/woocommerce/compare/v2.4.7...v2.4.8) (2018-02-27)

### [2.4.7](https://github.com/myparcelnl/woocommerce/compare/v2.4.6...v2.4.7) (2018-02-07)


### Bug Fixes

* action buttons ([9a2b66a](https://github.com/myparcelnl/woocommerce/commit/9a2b66a4ff6a1181086b84e52632e29c9eeca520))

### [2.4.6](https://github.com/myparcelnl/woocommerce/compare/v2.4.5...v2.4.6) (2018-02-01)

### [2.4.1](https://github.com/myparcelnl/woocommerce/compare/v2.4.0...v2.4.1) (2017-10-12)

## [2.4.0](https://github.com/myparcelnl/woocommerce/compare/v2.4.0-beta-3...v2.4.0) (2017-09-25)

## [2.4.0-beta-3](https://github.com/myparcelnl/woocommerce/compare/v2.4.0-beta-2...v2.4.0-beta-3) (2017-08-23)

## [2.4.0-beta-2](https://github.com/myparcelnl/woocommerce/compare/v2.4.0-beta-1...v2.4.0-beta-2) (2017-08-08)

## [2.4.0-beta-1](https://github.com/myparcelnl/woocommerce/compare/v2.3.3...v2.4.0-beta-1) (2017-07-13)

### [2.3.3](https://github.com/myparcelnl/woocommerce/compare/v2.3.2...v2.3.3) (2017-06-27)

### [2.3.2](https://github.com/myparcelnl/woocommerce/compare/v2.3.1...v2.3.2) (2017-06-26)

### [2.3.1](https://github.com/myparcelnl/woocommerce/compare/v2.3.0...v2.3.1) (2017-06-12)

## [2.3.0](https://github.com/myparcelnl/woocommerce/compare/v2.3.0-beta-5...v2.3.0) (2017-06-12)

## [2.3.0-beta-5](https://github.com/myparcelnl/woocommerce/compare/v2.3.0-beta-4...v2.3.0-beta-5) (2017-06-08)

## [2.3.0-beta-4](https://github.com/myparcelnl/woocommerce/compare/v2.3.0-beta-3...v2.3.0-beta-4) (2017-06-02)

## [2.3.0-beta-3](https://github.com/myparcelnl/woocommerce/compare/v2.3.0-beta-2...v2.3.0-beta-3) (2017-06-02)

## [2.3.0-beta-2](https://github.com/myparcelnl/woocommerce/compare/v2.3.0-beta-1...v2.3.0-beta-2) (2017-04-25)

## [2.3.0-beta-1](https://github.com/myparcelnl/woocommerce/compare/v2.2.0...v2.3.0-beta-1) (2017-04-11)

## [2.2.0](https://github.com/myparcelnl/woocommerce/compare/v2.2.0-RC1...v2.2.0) (2017-04-03)

## [2.2.0-RC1](https://github.com/myparcelnl/woocommerce/compare/v2.2.0-beta2...v2.2.0-RC1) (2017-03-29)

## [2.2.0-beta2](https://github.com/myparcelnl/woocommerce/compare/v2.2.0-beta1...v2.2.0-beta2) (2017-03-22)

## [2.2.0-beta1](https://github.com/myparcelnl/woocommerce/compare/v2.1.4-beta1...v2.2.0-beta1) (2017-03-21)

### [2.1.4-beta1](https://github.com/myparcelnl/woocommerce/compare/v2.1.3...v2.1.4-beta1) (2017-03-02)


### Reverts

* Revert "Translations update" ([76bfaa7](https://github.com/myparcelnl/woocommerce/commit/76bfaa7691e272b068842196a422840502b1a045))

### [2.1.3](https://github.com/myparcelnl/woocommerce/compare/v2.1.2...v2.1.3) (2017-02-01)

### [2.1.2](https://github.com/myparcelnl/woocommerce/compare/v2.1.1...v2.1.2) (2017-01-16)

### [2.1.1](https://github.com/myparcelnl/woocommerce/compare/v2.1.1-beta-1...v2.1.1) (2017-01-05)

### [2.1.1-beta-1](https://github.com/myparcelnl/woocommerce/compare/v2.1.0...v2.1.1-beta-1) (2016-12-28)

## [2.1.0](https://github.com/myparcelnl/woocommerce/compare/v2.1.0-beta-2...v2.1.0) (2016-12-06)

## [2.1.0-beta-2](https://github.com/myparcelnl/woocommerce/compare/v2.1.0-beta-1...v2.1.0-beta-2) (2016-12-02)

## [2.1.0-beta-1](https://github.com/myparcelnl/woocommerce/compare/v2.0.6-beta-3...v2.1.0-beta-1) (2016-11-30)

### [2.0.6-beta-3](https://github.com/myparcelnl/woocommerce/compare/v2.0.6-beta-2...v2.0.6-beta-3) (2016-11-29)

### [2.0.6-beta-2](https://github.com/myparcelnl/woocommerce/compare/v2.0.6-beta...v2.0.6-beta-2) (2016-11-25)

### [2.0.6-beta](https://github.com/myparcelnl/woocommerce/compare/v2.0.5...v2.0.6-beta) (2016-11-23)

### [2.0.5](https://github.com/myparcelnl/woocommerce/compare/v2.0.4...v2.0.5) (2016-11-21)

### [2.0.4](https://github.com/myparcelnl/woocommerce/compare/v2.0.3...v2.0.4) (2016-11-18)

### [2.0.3](https://github.com/myparcelnl/woocommerce/compare/v2.0.2...v2.0.3) (2016-11-18)

### [2.0.2](https://github.com/myparcelnl/woocommerce/compare/v2.0.1...v2.0.2) (2016-11-17)

### [2.0.1](https://github.com/myparcelnl/woocommerce/compare/v1.5.6...v2.0.1) (2016-11-16)

### [1.5.6](https://github.com/myparcelnl/woocommerce/compare/e36e4ab6b879ba21ec782447205d54f8b69c3a52...v1.5.6) (2016-04-26)


### Bug Fixes

* bug with copying address data from foreign addresses ([3b03351](https://github.com/myparcelnl/woocommerce/commit/3b03351b932c1b3a5db08152bc6c87a63c966732))
* error when opening pdf directly from modal ([e36e4ab](https://github.com/myparcelnl/woocommerce/commit/e36e4ab6b879ba21ec782447205d54f8b69c3a52))


### Reverts

* Revert "smarter checkout re-ordening" ([125d27e](https://github.com/myparcelnl/woocommerce/commit/125d27e0d7437936c98a9bc925c21690d33abae9))
