import{S as c,M as y,L as _,h as p,_ as v,a as g,b as k,c as x,d as w,e as $,N as b,f as C,g as A}from"./BreadcrumbSeparator.vue_vue_type_script_setup_true_lang-B1zVAdHj.js";import{d as I,m as M,q as d,c as P,F as S,a as e,H as L,u as a,w as s,l as N,o as B}from"./app-Bha5sprQ.js";import{c as i}from"./createLucideIcon-D-TTKCjC.js";import{S as u}from"./star-C7deagm_.js";import{H}from"./heart-BOt4CJhn.js";import{E as V}from"./eye-nW07_u36.js";/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const z=i("BellIcon",[["path",{d:"M10.268 21a2 2 0 0 0 3.464 0",key:"vwvbt9"}],["path",{d:"M3.262 15.326A1 1 0 0 0 4 17h16a1 1 0 0 0 .74-1.673C19.41 13.956 18 12.499 18 8A6 6 0 0 0 6 8c0 4.499-1.411 5.956-2.738 7.326",key:"11g9vi"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const D=i("CreditCardIcon",[["rect",{width:"20",height:"14",x:"2",y:"5",rx:"2",key:"ynyp8z"}],["line",{x1:"2",x2:"22",y1:"10",y2:"10",key:"1b3vmo"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const E=i("LayoutDashboardIcon",[["rect",{width:"7",height:"9",x:"3",y:"3",rx:"1",key:"10lvy0"}],["rect",{width:"7",height:"5",x:"14",y:"3",rx:"1",key:"16une8"}],["rect",{width:"7",height:"9",x:"14",y:"12",rx:"1",key:"1hutg5"}],["rect",{width:"7",height:"5",x:"3",y:"16",rx:"1",key:"ldoo1y"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const O=i("PackageIcon",[["path",{d:"M11 21.73a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73z",key:"1a0edw"}],["path",{d:"M12 22V12",key:"d0xqtd"}],["path",{d:"m3.3 7 7.703 4.734a2 2 0 0 0 1.994 0L20.7 7",key:"yx3hmr"}],["path",{d:"m7.5 4.27 9 5.15",key:"1c824w"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const q=i("UserIcon",[["path",{d:"M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2",key:"975kel"}],["circle",{cx:"12",cy:"7",r:"4",key:"17ys0d"}]]),K=I({__name:"AppCustomerSidebar",setup(F){const h=M(),t=d(()=>{var r;return(r=h.props.auth)==null?void 0:r.customer_id}),f=[{title:"Dashboard",href:t.value?`/customers/${t.value}/dashboard`:"#",icon:E,isActive:!0},{title:"Account Overview",href:t.value?`/customers/${t.value}/dashboard`:"#",icon:q},{title:"Orders",href:"/orders/my-orders",icon:O},{title:"Pending Reviews",href:"#",icon:u},{title:"Wishlist",href:"/wishlist",icon:H},{title:"Recently Viewed",href:"#",icon:V},{title:"Account Management",icon:c,children:[{title:"Profile Settings",href:"/customer/profile",icon:c},{title:"Payment Settings",href:"#",icon:D},{title:"Address Book",href:t.value?`/customers/${t.value}/addresses`:"#",icon:y},{title:"Loyalty Points",href:t.value?`/customers/${t.value}/loyalty-points`:"#",icon:u},{title:"Newsletter Preferences",href:"#",icon:z},{title:"Close Account",href:"/customer/account",icon:_}]}],o=r=>r.filter(n=>{if(n.children){const l=o(n.children);if(l.length===0)return!1;n.children=l}return!0}),m=d(()=>o([...f]));return(r,n)=>(B(),P(S,null,[e(a(p),{collapsible:"icon",variant:"inset"},{default:s(()=>[e(a(v),null,{default:s(()=>[e(a(g),null,{default:s(()=>[e(a(k),null,{default:s(()=>[e(a(x),{size:"lg","as-child":""},{default:s(()=>[e(a(N),{href:t.value?`/customers/${t.value}/dashboard`:"/"},{default:s(()=>[e(w)]),_:1},8,["href"])]),_:1})]),_:1})]),_:1})]),_:1}),e(a($),null,{default:s(()=>[e(b,{items:m.value},null,8,["items"])]),_:1}),e(a(C),null,{default:s(()=>[e(A)]),_:1})]),_:1}),L(r.$slots,"default")],64))}});export{D as C,K as _};
