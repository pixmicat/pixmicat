// IE不支援display: table，故用此方法
document.write('<style type="text/css">.reply { display: inline ; zoom: 1; }</style>');

// 解決IE顯示回應區塊時排在同一行的問題
function IEdivfix(){
	var divs=document.getElementsByTagName('div'),divs_cnt=divs.length;
	for(i=0;i<divs_cnt;i++)
		if(divs[i].className.substr(0,5)=='reply') divs[i].insertAdjacentHTML('afterEnd','<br />');
}
hookPresetFunction(IEdivfix); // Hook on