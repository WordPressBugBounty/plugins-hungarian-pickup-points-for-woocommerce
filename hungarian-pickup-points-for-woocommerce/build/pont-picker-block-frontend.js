(()=>{var e={942:(e,t)=>{var o;!function(){"use strict";var n={}.hasOwnProperty;function c(){for(var e="",t=0;t<arguments.length;t++){var o=arguments[t];o&&(e=r(e,p(o)))}return e}function p(e){if("string"==typeof e||"number"==typeof e)return e;if("object"!=typeof e)return"";if(Array.isArray(e))return c.apply(null,e);if(e.toString!==Object.prototype.toString&&!e.toString.toString().includes("[native code]"))return e.toString();var t="";for(var o in e)n.call(e,o)&&e[o]&&(t=r(t,o));return t}function r(e,t){return t?e?e+" "+t:e+t:e}e.exports?(c.default=c,e.exports=c):void 0===(o=function(){return c}.apply(t,[]))||(e.exports=o)}()}},t={};function o(n){var c=t[n];if(void 0!==c)return c.exports;var p=t[n]={exports:{}};return e[n](p,p.exports,o),p.exports}o.n=e=>{var t=e&&e.__esModule?()=>e.default:()=>e;return o.d(t,{a:t}),t},o.d=(e,t)=>{for(var n in t)o.o(t,n)&&!o.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},o.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t),(()=>{"use strict";const e=window.wc.blocksCheckout,t=window.wc.wcBlocksSharedHocs,n=window.React,c=window.wp.element,p=(window.wp.i18n,window.wp.data),r=window.lodash,s=window.wc.wcSettings;var a=o(942),i=o.n(a);wp.hooks.addAction("experimental__woocommerce_blocks-checkout-set-active-payment-method","vp-woo-pont-block",(function(t){const{codFeesEnabled:o}=(0,s.getSetting)("vp-woo-pont-picker_data","");o&&(0,e.extensionCartUpdate)({namespace:"vp-woo-pont-picker",cartPropsToReceive:["extensions"],data:{payment_method:t.value}})})),wp.hooks.addAction("experimental__woocommerce_blocks-checkout-set-selected-shipping-rate","vp-woo-pont-block",(function(e){e&&e.shippingRateId&&(document.querySelector(".wp-block-woocommerce-checkout").dataset.VpShippingMethod=e.shippingRateId)}));const l=JSON.parse('{"apiVersion":2,"name":"vp-woo-pont/pont-picker-block","version":"2.0.0","title":"Pont Picker Block","category":"woocommerce","description":"Adds a pont picker field to the checkout form.","supports":{"html":false,"align":false,"multiple":false,"reusable":false},"parent":["woocommerce/checkout-pickup-options-block"],"textdomain":"vp-woo-pont","editorStyle":"file:../../../build/style-pont-picker-block.css","attributes":{"lock":{"type":"object","default":{"move":true,"remove":true}}}}');(0,e.registerCheckoutBlock)({metadata:l,component:(0,t.withFilteredAttributes)({text:{type:"string",default:"Select a pickup point"},selectButton:{type:"string",default:"Select pickup point"},changeButton:{type:"string",default:"Modify"},showIcons:{type:"boolean",default:!0}})((({text:t,selectButton:o,changeButton:a,showIcons:l,checkoutExtensionData:d,extensions:u,cart:m})=>{const{setExtensionData:w}=d,[k,v]=((0,c.useCallback)((0,r.debounce)(((e,t,o)=>{w(e,t,o)}),1e3),[w]),(0,c.useState)((()=>u["vp-woo-pont-picker"].shipping_costs))),f=u["vp-woo-pont-picker"]&&u["vp-woo-pont-picker"].selected_pont,[h,b]=(0,c.useState)(f||!1),[g,y]=(0,c.useState)(!1);(0,c.useEffect)((()=>{const e=()=>{_()};return jQuery(document).on("vp_woo_pont_modal_point_selected",e),()=>{jQuery(document).off("vp_woo_pont_modal_point_selected",e)}}),[]);const _=()=>{y(!0),(0,e.extensionCartUpdate)({namespace:"vp-woo-pont-picker",cartPropsToReceive:["extensions"]}).then((e=>{y(!1)}))},{prefersCollection:S}=(0,p.useSelect)((e=>({prefersCollection:e("wc/store/checkout").prefersCollection()}))),x=(0,p.useSelect)((e=>e("wc/store/cart").getCartData().extensions["vp-woo-pont-picker"]));(0,c.useEffect)((()=>{b(x.selected_pont)}),[x]);const E=(0,s.getSetting)("collectableMethodIds"),N=1==m.shippingRates[0].shipping_rates.filter((e=>E.includes(e.method_id))).length,C=m.shippingRates[0].shipping_rates.find((e=>e.selected&&"vp_pont"===e.method_id));if(document.querySelector(".wp-block-woocommerce-checkout").dataset.VpShippingMethod=C?"vp_pont":"",document.querySelector(".wp-block-woocommerce-checkout").dataset.VpSelectedPoint=h?h.id:"",C||S&&N)return(0,n.createElement)("div",{className:i()("vp-woo-pont-block",{"wc-block-components-loading-mask":g})},(0,n.createElement)("div",{className:"vp-woo-pont-block-box wc-block-components-loading-mask__children"},h?(0,n.createElement)("div",{className:"vp-woo-pont-block-selected"},(0,n.createElement)("i",{className:`vp-woo-pont-provider-icon-${h.provider}`}),(0,n.createElement)("div",{className:"vp-woo-pont-block-selected-info"},(0,n.createElement)("strong",null,h.name),(0,n.createElement)("br",null),(0,n.createElement)("span",null,h.addr,", ",h.zip," ",h.city))):(0,n.createElement)("div",{className:"vp-woo-pont-block-select"},(0,n.createElement)("p",{className:"vp-woo-pont-block-select-title"},t),l&&(0,n.createElement)("div",{className:"vp-woo-pont-block-select-icons"},k&&Object.keys(k).map((e=>(0,n.createElement)("i",{key:e,className:`vp-woo-pont-provider-icon-${e}`}))))),(0,n.createElement)("div",{className:"vp-woo-pont-block-select-button","data-shipping-costs":JSON.stringify(k)},(0,n.createElement)(e.Button,{className:"vp-woo-pont-show-map"},h?a:o))),g&&(0,n.createElement)("span",{className:"wc-block-components-spinner","aria-hidden":"true"}))}))})})()})();