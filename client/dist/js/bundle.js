!function(n){function e(i){if(t[i])return t[i].exports;var o=t[i]={i:i,l:!1,exports:{}};return n[i].call(o.exports,o,o.exports,e),o.l=!0,o.exports}var t={};e.m=n,e.c=t,e.i=function(n){return n},e.d=function(n,t,i){e.o(n,t)||Object.defineProperty(n,t,{configurable:!1,enumerable:!0,get:i})},e.n=function(n){var t=n&&n.__esModule?function(){return n.default}:function(){return n};return e.d(t,"a",t),t},e.o=function(n,e){return Object.prototype.hasOwnProperty.call(n,e)},e.p="",e(e.s="./client/src/bundles/bundle.js")}({"./client/src/bundles/CheckForUpdates.js":function(n,e){window.jQuery.entwine("ss",function(n){n("#checkForUpdates").entwine({PollTimeout:null,onclick:function(){this.setLoading()},onmatch:function(){this.getButton(!0).length&&this.setLoading()},setLoading:function(){var e=this.getButton().data("message");n(".ss-gridfield-buttonrow").first().prepend('<p class="message warning">'+e+"</p>"),this.poll()},poll:function(){var e=this;n.ajax({url:this.getButton().data("check"),async:!0,success:function(n){e.clearLoading(JSON.parse(n))},error:function(n){"undefined"!=typeof console&&console.log(n)}})},getButton:function(n){var e="button";return n&&(e+=":disabled"),this.children(e).first()},clearLoading:function(e){if(!1===e)return void this.closest("fieldset.ss-gridfield").reload();clearTimeout(this.getPollTimeout()),this.setPollTimeout(setTimeout(function(){n("#checkForUpdates").poll()},5e3))}})})},"./client/src/bundles/GridfieldDropdownFilter.js":function(n,e){window.jQuery.entwine("ss",function(n){n(".gridfield-dropdown-filter select").entwine({onchange:function(){this.parent().find(".action").click()}})})},"./client/src/bundles/bundle.js":function(n,e,t){t("./client/src/bundles/CheckForUpdates.js"),t("./client/src/bundles/GridfieldDropdownFilter.js")}});