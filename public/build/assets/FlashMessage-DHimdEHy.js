import{d as m,m as v,q as y,r as n,z as h,C as l,T as x,w as g,c as k,i as o,b as c,u as i,t as _,a as C,x as b,o as t}from"./app-DmCSe9pP.js";import{c as u}from"./createLucideIcon-D8jDYPjc.js";import{X as w,_ as I}from"./_plugin-vue_export-helper-BHVHNv9a.js";/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const B=u("CircleAlertIcon",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["line",{x1:"12",x2:"12",y1:"8",y2:"12",key:"1pkeuh"}],["line",{x1:"12",x2:"12.01",y1:"16",y2:"16",key:"4dfq90"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const M=u("CircleCheckBigIcon",[["path",{d:"M21.801 10A10 10 0 1 1 17 3.335",key:"yps3ct"}],["path",{d:"m9 11 3 3L22 4",key:"1pflzl"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const z=u("InfoIcon",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M12 16v-4",key:"1dtifu"}],["path",{d:"M12 8h.01",key:"e9boi3"}]]),A={class:"flex items-start space-x-3"},F={class:"pt-0.5"},N={class:"flex-1 text-sm text-gray-800"},T=m({__name:"FlashMessage",setup(V){const f=v(),p=y(()=>f.props.flash||{}),r=n(!1),e=n(null),s=n("");return h(()=>p.value,a=>{a.success?(e.value="success",s.value=a.success):a.error?(e.value="error",s.value=a.error):a.info?(e.value="info",s.value=a.info):(e.value=null,s.value=""),s.value&&(r.value=!0,setTimeout(()=>{r.value=!1},4e3))},{immediate:!0,deep:!0}),(a,d)=>(t(),l(x,{name:"fade"},{default:g(()=>[r.value&&s.value?(t(),k("div",{key:0,class:b(["fixed top-4 right-4 z-50 w-[90%] max-w-sm rounded-lg border-l-4 bg-white p-5 shadow-lg ring-1 ring-black/5",{"border-green-500":e.value==="success","border-red-500":e.value==="error","border-blue-500":e.value==="info"}])},[c("div",A,[c("div",F,[e.value==="success"?(t(),l(i(M),{key:0,class:"h-6 w-6 text-green-500"})):o("",!0),e.value==="error"?(t(),l(i(B),{key:1,class:"h-6 w-6 text-red-500"})):o("",!0),e.value==="info"?(t(),l(i(z),{key:2,class:"h-6 w-6 text-blue-500"})):o("",!0)]),c("div",N,_(s.value),1),c("button",{onClick:d[0]||(d[0]=q=>r.value=!1),class:"text-gray-400 transition hover:text-gray-600"},[C(i(w),{class:"h-4 w-4"})])])],2)):o("",!0)]),_:1}))}}),P=I(T,[["__scopeId","data-v-0293f074"]]);export{M as C,P as F,z as I};
