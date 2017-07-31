<?php

namespace uploader;
/**
* This file defines functionality for the upload class.
* @license GPL
* @license http://opensource.org/licenses/gpl-license.php GNU Public License.
*/
/**
 * @var bool
 * Set the debugging constant to true if you are getting error messages or uploads are failing
 */
define("DEBUGGING",false);
 
/**
 * 
 * @author ryanolee
 * @version 1.0
 * A class to allow for upload of files to specified locations on the given server. Should handle all client and severside interaction during upload of files via AJAX.This includes:Genaration of HTML for client.The handling of javascript to send the ajax request to te right place and send back feedback.To store session variables so uploads can be accepted and/or validated before being places at the given directory.
 * @property string $upload_dir The directory to upload files to.
 * @property int $upload_id A unique identifier for the class instance.
 * @property int $upload_limit The maximum sized files can be uploaded in (in bytes.)
 * @property string[] $accepted_datatypes The list of mime types that are acceptable for any given upload.
 * @property string $file_name The name of the file after it has been uploaded (File extention should not be given.)
 * @property string $upload_endpoint where the form should point to for file uploads to be allowed.
 */

class upload{
	private static $uploader_id=0;
	/**
	 * 
	 * @param string $dir_to_upload_to gives a specified upload directory 
	 * @param array $accepted_datatypes A list of the accepted datatypes (must be in mime format)
	 * @param string $file_name The file name that should be held as the name of the file upon it being uploaded 
	 * @param string $upload_endpoint The endpoint the upload handle should try to upload information to.
	 * @param number $max_size maximum accepted size of file uploaded(in bytes)
	 * 
	 * 
	 */
	public function __construct($dir_to_upload_to,$accepted_datatypes,$file_name,$upload_endpoint,$max_size=52428800){
		$this->upload_dir=$dir_to_upload_to;
		$this->upload_id=self::get_upload_id();
		$this->upload_limit=$max_size;
		$this->accepted_datatypes=is_array($accepted_datatypes)?$accepted_datatypes:array($accepted_datatypes);
		$this->file_name=$file_name;
		$this->upload_endpoint=$upload_endpoint;
		$this->csrf_token=base64_encode((string)mt_rand(0,mt_getrandmax()));
		if(session_status() == PHP_SESSION_NONE){
			if(!session_start()){
				throw new \Exception("Unable to begin or resume session with client.");
			}
		};
			
		
		if(!isset($_SESSION["upload_handles"])){
			$_SESSION["upload_handles"]=array();
		}
		$_SESSION["upload_handles"][(string)$this->upload_id]=serialize($this);
	}
	public function get_html_segment($to_return=""){//@todo DO NOT FORGET TO POST FILE_NO SO THAT FILE CAN BE IDENTIFIED WHEN IT IS RETURNED TO THE SERVER;
		if ($this->upload_id==1){//if this is the first block of html that is looking to be returned.
			//Insert jquerey ajax upload handler
			$to_return.="
					<script type='text/javascript'>//Progress handle by https://gist.github.com/nebirhos/3892018
(function addXhrProgressEvent($) {
    var originalXhr = $.ajaxSettings.xhr;
    $.ajaxSetup({
        xhr: function() {
            var req = originalXhr(),
                that = this;
            if (req) {
                if (typeof req.addEventListener == \"function\" &&
                    that.progress !== undefined) {
                    req.addEventListener(\"progress\", function(
                        evt) {
                        that.progress(evt);
                    }, false);
                }
                if (typeof req.upload == \"object\" && that.progressUpload !==
                    undefined) {
                    req.upload.addEventListener(\"progress\",
                        function(evt) {
                            that.progressUpload(evt);
                        }, false);
                }
            }
            return req;
        }
    });
})(jQuery);
$(document).ready(function() {
	\$(\"form[id='http_upload_form']\").each(function(){\$(this).append(\"<input type='hidden' name='js_enabled' value='yes'>\")});
	window.upload_handles=[];//list of identifying DOM objects that can,in turn, lead to all relavent information on the upload being found.
    function handle_file_button_click(upload_button,called) {
            upload_button=upload_button||this;//Make upload_button optional so that the script can be passed a upload button argument
            console.log(upload_button);
            if(typeof upload_button[\"toElement\"] != 'undefined'){//if is event
            	upload_button=upload_button[\"toElement\"]//convert to object
            };//grab element (not event handle)
            called=called||false;//make tries optional so that uploads can be retried on error.
    		//get the file id of the file we are looking for
            if(!called){
            	window.upload_handles.push(upload_button);//add button to upload handles
            }
    	    if(window.upload_handles.length>1&&!called){
    	    	$(upload_button).html(\"Upload queued\");
    	    	$(upload_button).off(\"click\")//Unbind event handle so that multiple requests are not spammed
    	    	console.log(\"uploads still queued!\");
    	    	return null;
    	    }
            console.log('Beggining Ajax file upload.')
            
            upload_file_id = $(upload_button).attr(\"upload_button_id\");
            selected_file = $(\"input[type='file'][file_no='\" + toString(upload_file_id) + \"']\"); //find the bound file
            upload_file_number = $(selected_file).attr(\"file_no\"); //the linked file number
            upload_form = $(upload_button).closest(\"form\"); //get the form
            validation_area= $(upload_form).find(\"#input_validate_area\");
            var current_upload_progress_bar=$(upload_form).parent().find(\"progress\");
            //$(upload_form).append('<input type=\'hidden\' name=\'cookies\' value=\''+document.cookie+'\'/>');
            
            
            $(upload_form).ajaxSubmit({
            	beforeSend: function(xhr){
            		window.current_upload_request=xhr;
            		$(upload_form).css(\"display\",\"none\");
            		$(current_upload_progress_bar).closest(\"#post_upload_area\").css(\"display\",\"block\");
            	},
                success: function(data) {
                	$(upload_form).css(\"display\",\"block\");
                	$(current_upload_progress_bar).closest(\"#post_upload_area\").css(\"display\",\"none\");
                    if(data[\"valid\"]){
                    	$(upload_form).parent().html(\"<div style='background-color:#33cc33'>\"+data[\"message\"]+\"</div>\")
                    }
                    else{
                    	$(validation_area).html(data[\"message\"])
                    	$(upload_button).off(\"click\");
                    	$(upload_button).on(\"click\", handle_file_button_click)
                    	$(upload_button).html(\"Click here to upload!\");
                    }
                },
                error:function(){
                	$(upload_form).css(\"display\",\"block\");
                	$(current_upload_progress_bar).closest(\"#post_upload_area\").css(\"display\",\"none\");
                	$(validation_area).html(\"Error:Upload aborted\");
                	$(upload_button).off(\"click\");
                	$(upload_button).on(\"click\", handle_file_button_click)
                	$(upload_button).html(\"Click here to upload!\");
                },
                complete:function(){
                	////////////////////////////////////////NEXT ITEM/////////////////////////////////////
                	window.upload_handles.shift();//remove completed upload
                    if(window.upload_handles.length>=1){//checked for any qued uploads
                    	handle_file_button_click(window.upload_handles[0],true);//handle next item;
                    	
                    }
                },
                progressUpload: function(evt) {
                    if (evt.lengthComputable) {
                        console.log(\"Loaded \" + parseInt((
                            evt.loaded / evt.total *
                            100), 10) + \"%\");
                        $(current_upload_progress_bar).val(evt.loaded / evt.total *100)//update upload progress bar :)
                    }
                }
            });
            /*form_data= new FormData($(upload_form)[0]);//convert it into form data
								 $('#file_upload')
								 console.log(upload_form);
								 $.ajax(upload_location,{
								        url: upload_location,
								        type: 'POST',
								        xhr: function() {
								            xhr = new window.XMLHttpRequest();
								            xhr.upload.addEventListener(\"progress\", function(evt) {
								                if (evt.lengthComputable) {
								                     percent_complete = evt.loaded / evt.total;
								                    console.log(percent_complete)
								                }
								           }, false);
								        },
								        data:form_data,
								        //async: false,
								        success: function (data) {
								            alert(data)
								        },
								        cache: false,
								        contentType: false,
								        processData: false
								    });*/
        }
        //function send_upload_request(file){
        //}
    setTimeout(function() {
        //$.getScript('./js/ajax/form.js');
        $(\"button[id='http_upload_button']\").on(\"click\",
            handle_file_button_click);
        $('form[id=\"http_upload_form\"]').on('submit', function() {
            return false;
        });
        $(\"button[id='abort_upload_button']\").on(\"click\",function(){window.current_upload_request.abort()}); //block form functionality
    }, 50); //defer event handle for 0.5 secs after script execution
});</script><script>
/*!
 * jQuery Form Plugin
 * version: 3.51.0-2014.06.20
 * Requires jQuery v1.5 or later
 * Copyright (c) 2014 M. Alsup
 * Examples and documentation at: http://malsup.com/jquery/form/
 * Project repository: https://github.com/malsup/form
 * Dual licensed under the MIT and GPL licenses.
 * https://github.com/malsup/form#copyright-and-license
 */
!function(e){\"use strict\";\"function\"==typeof define&&define.amd?define([\"jquery\"],e):e(\"undefined\"!=typeof jQuery?jQuery:window.Zepto)}(function(e){\"use strict\";function t(t){var r=t.data;t.isDefaultPrevented()||(t.preventDefault(),e(t.target).ajaxSubmit(r))}function r(t){var r=t.target,a=e(r);if(!a.is(\"[type=submit],[type=image]\")){var n=a.closest(\"[type=submit]\");if(0===n.length)return;r=n[0]}var i=this;if(i.clk=r,\"image\"==r.type)if(void 0!==t.offsetX)i.clk_x=t.offsetX,i.clk_y=t.offsetY;else if(\"function\"==typeof e.fn.offset){var o=a.offset();i.clk_x=t.pageX-o.left,i.clk_y=t.pageY-o.top}else i.clk_x=t.pageX-r.offsetLeft,i.clk_y=t.pageY-r.offsetTop;setTimeout(function(){i.clk=i.clk_x=i.clk_y=null},100)}function a(){if(e.fn.ajaxSubmit.debug){var t=\"[jquery.form] \"+Array.prototype.join.call(arguments,\"\");window.console&&window.console.log?window.console.log(t):window.opera&&window.opera.postError&&window.opera.postError(t)}}var n={};n.fileapi=void 0!==e(\"<input type='file'/>\").get(0).files,n.formdata=void 0!==window.FormData;var i=!!e.fn.prop;e.fn.attr2=function(){if(!i)return this.attr.apply(this,arguments);var e=this.prop.apply(this,arguments);return e&&e.jquery||\"string\"==typeof e?e:this.attr.apply(this,arguments)},e.fn.ajaxSubmit=function(t){function r(r){var a,n,i=e.param(r,t.traditional).split(\"&\"),o=i.length,s=[];for(a=0;o>a;a++)i[a]=i[a].replace(/\+/g,\" \"),n=i[a].split(\"=\"),s.push([decodeURIComponent(n[0]),decodeURIComponent(n[1])]);return s}function o(a){for(var n=new FormData,i=0;i<a.length;i++)n.append(a[i].name,a[i].value);if(t.extraData){var o=r(t.extraData);for(i=0;i<o.length;i++)o[i]&&n.append(o[i][0],o[i][1])}t.data=null;var s=e.extend(!0,{},e.ajaxSettings,t,{contentType:!1,processData:!1,cache:!1,type:u||\"POST\"});t.uploadProgress&&(s.xhr=function(){var r=e.ajaxSettings.xhr();return r.upload&&r.upload.addEventListener(\"progress\",function(e){var r=0,a=e.loaded||e.position,n=e.total;e.lengthComputable&&(r=Math.ceil(a/n*100)),t.uploadProgress(e,a,n,r)},!1),r}),s.data=null;var c=s.beforeSend;return s.beforeSend=function(e,r){r.data=t.formData?t.formData:n,c&&c.call(this,e,r)},e.ajax(s)}function s(r){function n(e){var t=null;try{e.contentWindow&&(t=e.contentWindow.document)}catch(r){a(\"cannot get iframe.contentWindow document: \"+r)}if(t)return t;try{t=e.contentDocument?e.contentDocument:e.document}catch(r){a(\"cannot get iframe.contentDocument: \"+r),t=e.document}return t}function o(){function t(){try{var e=n(g).readyState;a(\"state = \"+e),e&&\"uninitialized\"==e.toLowerCase()&&setTimeout(t,50)}catch(r){a(\"Server abort: \",r,\" (\",r.name,\")\"),s(k),j&&clearTimeout(j),j=void 0}}var r=f.attr2(\"target\"),i=f.attr2(\"action\"),o=\"multipart/form-data\",c=f.attr(\"enctype\")||f.attr(\"encoding\")||o;w.setAttribute(\"target\",p),(!u||/post/i.test(u))&&w.setAttribute(\"method\",\"POST\"),i!=m.url&&w.setAttribute(\"action\",m.url),m.skipEncodingOverride||u&&!/post/i.test(u)||f.attr({encoding:\"multipart/form-data\",enctype:\"multipart/form-data\"}),m.timeout&&(j=setTimeout(function(){T=!0,s(D)},m.timeout));var l=[];try{if(m.extraData)for(var d in m.extraData)m.extraData.hasOwnProperty(d)&&l.push(e.isPlainObject(m.extraData[d])&&m.extraData[d].hasOwnProperty(\"name\")&&m.extraData[d].hasOwnProperty(\"value\")?e('<input type=\"hidden\" name=\"'+m.extraData[d].name+'\">').val(m.extraData[d].value).appendTo(w)[0]:e('<input type=\"hidden\" name=\"'+d+'\">').val(m.extraData[d]).appendTo(w)[0]);m.iframeTarget||v.appendTo(\"body\"),g.attachEvent?g.attachEvent(\"onload\",s):g.addEventListener(\"load\",s,!1),setTimeout(t,15);try{w.submit()}catch(h){var x=document.createElement(\"form\").submit;x.apply(w)}}finally{w.setAttribute(\"action\",i),w.setAttribute(\"enctype\",c),r?w.setAttribute(\"target\",r):f.removeAttr(\"target\"),e(l).remove()}}function s(t){if(!x.aborted&&!F){if(M=n(g),M||(a(\"cannot access response document\"),t=k),t===D&&x)return x.abort(\"timeout\"),void S.reject(x,\"timeout\");if(t==k&&x)return x.abort(\"server abort\"),void S.reject(x,\"error\",\"server abort\");if(M&&M.location.href!=m.iframeSrc||T){g.detachEvent?g.detachEvent(\"onload\",s):g.removeEventListener(\"load\",s,!1);var r,i=\"success\";try{if(T)throw\"timeout\";var o=\"xml\"==m.dataType||M.XMLDocument||e.isXMLDoc(M);if(a(\"isXml=\"+o),!o&&window.opera&&(null===M.body||!M.body.innerHTML)&&--O)return a(\"requeing onLoad callback, DOM not available\"),void setTimeout(s,250);var u=M.body?M.body:M.documentElement;x.responseText=u?u.innerHTML:null,x.responseXML=M.XMLDocument?M.XMLDocument:M,o&&(m.dataType=\"xml\"),x.getResponseHeader=function(e){var t={\"content-type\":m.dataType};return t[e.toLowerCase()]},u&&(x.status=Number(u.getAttribute(\"status\"))||x.status,x.statusText=u.getAttribute(\"statusText\")||x.statusText);var c=(m.dataType||\"\").toLowerCase(),l=/(json|script|text)/.test(c);if(l||m.textarea){var f=M.getElementsByTagName(\"textarea\")[0];if(f)x.responseText=f.value,x.status=Number(f.getAttribute(\"status\"))||x.status,x.statusText=f.getAttribute(\"statusText\")||x.statusText;else if(l){var p=M.getElementsByTagName(\"pre\")[0],h=M.getElementsByTagName(\"body\")[0];p?x.responseText=p.textContent?p.textContent:p.innerText:h&&(x.responseText=h.textContent?h.textContent:h.innerText)}}else\"xml\"==c&&!x.responseXML&&x.responseText&&(x.responseXML=X(x.responseText));try{E=_(x,c,m)}catch(y){i=\"parsererror\",x.error=r=y||i}}catch(y){a(\"error caught: \",y),i=\"error\",x.error=r=y||i}x.aborted&&(a(\"upload aborted\"),i=null),x.status&&(i=x.status>=200&&x.status<300||304===x.status?\"success\":\"error\"),\"success\"===i?(m.success&&m.success.call(m.context,E,\"success\",x),S.resolve(x.responseText,\"success\",x),d&&e.event.trigger(\"ajaxSuccess\",[x,m])):i&&(void 0===r&&(r=x.statusText),m.error&&m.error.call(m.context,x,i,r),S.reject(x,\"error\",r),d&&e.event.trigger(\"ajaxError\",[x,m,r])),d&&e.event.trigger(\"ajaxComplete\",[x,m]),d&&!--e.active&&e.event.trigger(\"ajaxStop\"),m.complete&&m.complete.call(m.context,x,i),F=!0,m.timeout&&clearTimeout(j),setTimeout(function(){m.iframeTarget?v.attr(\"src\",m.iframeSrc):v.remove(),x.responseXML=null},100)}}}var c,l,m,d,p,v,g,x,y,b,T,j,w=f[0],S=e.Deferred();if(S.abort=function(e){x.abort(e)},r)for(l=0;l<h.length;l++)c=e(h[l]),i?c.prop(\"disabled\",!1):c.removeAttr(\"disabled\");if(m=e.extend(!0,{},e.ajaxSettings,t),m.context=m.context||m,p=\"jqFormIO\"+(new Date).getTime(),m.iframeTarget?(v=e(m.iframeTarget),b=v.attr2(\"name\"),b?p=b:v.attr2(\"name\",p)):(v=e('<iframe name=\"'+p+'\" src=\"'+m.iframeSrc+'\" />'),v.css({position:\"absolute\",top:\"-1000px\",left:\"-1000px\"})),g=v[0],x={aborted:0,responseText:null,responseXML:null,status:0,statusText:\"n/a\",getAllResponseHeaders:function(){},getResponseHeader:function(){},setRequestHeader:function(){},abort:function(t){var r=\"timeout\"===t?\"timeout\":\"aborted\";a(\"aborting upload... \"+r),this.aborted=1;try{g.contentWindow.document.execCommand&&g.contentWindow.document.execCommand(\"Stop\")}catch(n){}v.attr(\"src\",m.iframeSrc),x.error=r,m.error&&m.error.call(m.context,x,r,t),d&&e.event.trigger(\"ajaxError\",[x,m,r]),m.complete&&m.complete.call(m.context,x,r)}},d=m.global,d&&0===e.active++&&e.event.trigger(\"ajaxStart\"),d&&e.event.trigger(\"ajaxSend\",[x,m]),m.beforeSend&&m.beforeSend.call(m.context,x,m)===!1)return m.global&&e.active--,S.reject(),S;if(x.aborted)return S.reject(),S;y=w.clk,y&&(b=y.name,b&&!y.disabled&&(m.extraData=m.extraData||{},m.extraData[b]=y.value,\"image\"==y.type&&(m.extraData[b+\".x\"]=w.clk_x,m.extraData[b+\".y\"]=w.clk_y)));var D=1,k=2,A=e(\"meta[name=csrf-token]\").attr(\"content\"),L=e(\"meta[name=csrf-param]\").attr(\"content\");L&&A&&(m.extraData=m.extraData||{},m.extraData[L]=A),m.forceSync?o():setTimeout(o,10);var E,M,F,O=50,X=e.parseXML||function(e,t){return window.ActiveXObject?(t=new ActiveXObject(\"Microsoft.XMLDOM\"),t.async=\"false\",t.loadXML(e)):t=(new DOMParser).parseFromString(e,\"text/xml\"),t&&t.documentElement&&\"parsererror\"!=t.documentElement.nodeName?t:null},C=e.parseJSON||function(e){return window.eval(\"(\"+e+\")\")},_=function(t,r,a){var n=t.getResponseHeader(\"content-type\")||\"\",i=\"xml\"===r||!r&&n.indexOf(\"xml\")>=0,o=i?t.responseXML:t.responseText;return i&&\"parsererror\"===o.documentElement.nodeName&&e.error&&e.error(\"parsererror\"),a&&a.dataFilter&&(o=a.dataFilter(o,r)),\"string\"==typeof o&&(\"json\"===r||!r&&n.indexOf(\"json\")>=0?o=C(o):(\"script\"===r||!r&&n.indexOf(\"javascript\")>=0)&&e.globalEval(o)),o};return S}if(!this.length)return a(\"ajaxSubmit: skipping submit process - no element selected\"),this;var u,c,l,f=this;\"function\"==typeof t?t={success:t}:void 0===t&&(t={}),u=t.type||this.attr2(\"method\"),c=t.url||this.attr2(\"action\"),l=\"string\"==typeof c?e.trim(c):\"\",l=l||window.location.href||\"\",l&&(l=(l.match(/^([^#]+)/)||[])[1]),t=e.extend(!0,{url:l,success:e.ajaxSettings.success,type:u||e.ajaxSettings.type,iframeSrc:/^https/i.test(window.location.href||\"\")?\"javascript:false\":\"about:blank\"},t);var m={};if(this.trigger(\"form-pre-serialize\",[this,t,m]),m.veto)return a(\"ajaxSubmit: submit vetoed via form-pre-serialize trigger\"),this;if(t.beforeSerialize&&t.beforeSerialize(this,t)===!1)return a(\"ajaxSubmit: submit aborted via beforeSerialize callback\"),this;var d=t.traditional;void 0===d&&(d=e.ajaxSettings.traditional);var p,h=[],v=this.formToArray(t.semantic,h);if(t.data&&(t.extraData=t.data,p=e.param(t.data,d)),t.beforeSubmit&&t.beforeSubmit(v,this,t)===!1)return a(\"ajaxSubmit: submit aborted via beforeSubmit callback\"),this;if(this.trigger(\"form-submit-validate\",[v,this,t,m]),m.veto)return a(\"ajaxSubmit: submit vetoed via form-submit-validate trigger\"),this;var g=e.param(v,d);p&&(g=g?g+\"&\"+p:p),\"GET\"==t.type.toUpperCase()?(t.url+=(t.url.indexOf(\"?\")>=0?\"&\":\"?\")+g,t.data=null):t.data=g;var x=[];if(t.resetForm&&x.push(function(){f.resetForm()}),t.clearForm&&x.push(function(){f.clearForm(t.includeHidden)}),!t.dataType&&t.target){var y=t.success||function(){};x.push(function(r){var a=t.replaceTarget?\"replaceWith\":\"html\";e(t.target)[a](r).each(y,arguments)})}else t.success&&x.push(t.success);if(t.success=function(e,r,a){for(var n=t.context||this,i=0,o=x.length;o>i;i++)x[i].apply(n,[e,r,a||f,f])},t.error){var b=t.error;t.error=function(e,r,a){var n=t.context||this;b.apply(n,[e,r,a,f])}}if(t.complete){var T=t.complete;t.complete=function(e,r){var a=t.context||this;T.apply(a,[e,r,f])}}var j=e(\"input[type=file]:enabled\",this).filter(function(){return\"\"!==e(this).val()}),w=j.length>0,S=\"multipart/form-data\",D=f.attr(\"enctype\")==S||f.attr(\"encoding\")==S,k=n.fileapi&&n.formdata;a(\"fileAPI :\"+k);var A,L=(w||D)&&!k;t.iframe!==!1&&(t.iframe||L)?t.closeKeepAlive?e.get(t.closeKeepAlive,function(){A=s(v)}):A=s(v):A=(w||D)&&k?o(v):e.ajax(t),f.removeData(\"jqxhr\").data(\"jqxhr\",A);for(var E=0;E<h.length;E++)h[E]=null;return this.trigger(\"form-submit-notify\",[this,t]),this},e.fn.ajaxForm=function(n){if(n=n||{},n.delegation=n.delegation&&e.isFunction(e.fn.on),!n.delegation&&0===this.length){var i={s:this.selector,c:this.context};return!e.isReady&&i.s?(a(\"DOM not ready, queuing ajaxForm\"),e(function(){e(i.s,i.c).ajaxForm(n)}),this):(a(\"terminating; zero elements found by selector\"+(e.isReady?\"\":\" (DOM not ready)\")),this)}return n.delegation?(e(document).off(\"submit.form-plugin\",this.selector,t).off(\"click.form-plugin\",this.selector,r).on(\"submit.form-plugin\",this.selector,n,t).on(\"click.form-plugin\",this.selector,n,r),this):this.ajaxFormUnbind().bind(\"submit.form-plugin\",n,t).bind(\"click.form-plugin\",n,r)},e.fn.ajaxFormUnbind=function(){return this.unbind(\"submit.form-plugin click.form-plugin\")},e.fn.formToArray=function(t,r){var a=[];if(0===this.length)return a;var i,o=this[0],s=this.attr(\"id\"),u=t?o.getElementsByTagName(\"*\"):o.elements;if(u&&!/MSIE [678]/.test(navigator.userAgent)&&(u=e(u).get()),s&&(i=e(':input[form=\"'+s+'\"]').get(),i.length&&(u=(u||[]).concat(i))),!u||!u.length)return a;var c,l,f,m,d,p,h;for(c=0,p=u.length;p>c;c++)if(d=u[c],f=d.name,f&&!d.disabled)if(t&&o.clk&&\"image\"==d.type)o.clk==d&&(a.push({name:f,value:e(d).val(),type:d.type}),a.push({name:f+\".x\",value:o.clk_x},{name:f+\".y\",value:o.clk_y}));else if(m=e.fieldValue(d,!0),m&&m.constructor==Array)for(r&&r.push(d),l=0,h=m.length;h>l;l++)a.push({name:f,value:m[l]});else if(n.fileapi&&\"file\"==d.type){r&&r.push(d);var v=d.files;if(v.length)for(l=0;l<v.length;l++)a.push({name:f,value:v[l],type:d.type});else a.push({name:f,value:\"\",type:d.type})}else null!==m&&\"undefined\"!=typeof m&&(r&&r.push(d),a.push({name:f,value:m,type:d.type,required:d.required}));if(!t&&o.clk){var g=e(o.clk),x=g[0];f=x.name,f&&!x.disabled&&\"image\"==x.type&&(a.push({name:f,value:g.val()}),a.push({name:f+\".x\",value:o.clk_x},{name:f+\".y\",value:o.clk_y}))}return a},e.fn.formSerialize=function(t){return e.param(this.formToArray(t))},e.fn.fieldSerialize=function(t){var r=[];return this.each(function(){var a=this.name;if(a){var n=e.fieldValue(this,t);if(n&&n.constructor==Array)for(var i=0,o=n.length;o>i;i++)r.push({name:a,value:n[i]});else null!==n&&\"undefined\"!=typeof n&&r.push({name:this.name,value:n})}}),e.param(r)},e.fn.fieldValue=function(t){for(var r=[],a=0,n=this.length;n>a;a++){var i=this[a],o=e.fieldValue(i,t);null===o||\"undefined\"==typeof o||o.constructor==Array&&!o.length||(o.constructor==Array?e.merge(r,o):r.push(o))}return r},e.fieldValue=function(t,r){var a=t.name,n=t.type,i=t.tagName.toLowerCase();if(void 0===r&&(r=!0),r&&(!a||t.disabled||\"reset\"==n||\"button\"==n||(\"checkbox\"==n||\"radio\"==n)&&!t.checked||(\"submit\"==n||\"image\"==n)&&t.form&&t.form.clk!=t||\"select\"==i&&-1==t.selectedIndex))return null;if(\"select\"==i){var o=t.selectedIndex;if(0>o)return null;for(var s=[],u=t.options,c=\"select-one\"==n,l=c?o+1:u.length,f=c?o:0;l>f;f++){var m=u[f];if(m.selected){var d=m.value;if(d||(d=m.attributes&&m.attributes.value&&!m.attributes.value.specified?m.text:m.value),c)return d;s.push(d)}}return s}return e(t).val()},e.fn.clearForm=function(t){return this.each(function(){e(\"input,select,textarea\",this).clearFields(t)})},e.fn.clearFields=e.fn.clearInputs=function(t){var r=/^(?:color|date|datetime|email|month|number|password|range|search|tel|text|time|url|week)$/i;return this.each(function(){var a=this.type,n=this.tagName.toLowerCase();r.test(a)||\"textarea\"==n?this.value=\"\":\"checkbox\"==a||\"radio\"==a?this.checked=!1:\"select\"==n?this.selectedIndex=-1:\"file\"==a?/MSIE/.test(navigator.userAgent)?e(this).replaceWith(e(this).clone(!0)):e(this).val(\"\"):t&&(t===!0&&/hidden/.test(a)||\"string\"==typeof t&&e(this).is(t))&&(this.value=\"\")})},e.fn.resetForm=function(){return this.each(function(){(\"function\"==typeof this.reset||\"object\"==typeof this.reset&&!this.reset.nodeType)&&this.reset()})},e.fn.enable=function(e){return void 0===e&&(e=!0),this.each(function(){this.disabled=!e})},e.fn.selected=function(t){return void 0===t&&(t=!0),this.each(function(){var r=this.type;if(\"checkbox\"==r||\"radio\"==r)this.checked=t;else if(\"option\"==this.tagName.toLowerCase()){var a=e(this).parent(\"select\");t&&a[0]&&\"select-one\"==a[0].type&&a.find(\"option\").selected(!1),this.selected=t}})},e.fn.ajaxSubmit.debug=!1});
</script>";
		}
		
		$to_return.="<div id='form_wrapper'><form action='".$this->upload_endpoint."' method='post' enctype='multipart/form-data' id='http_upload_form'><div class='row'><div class='six columns'><input file_no='".(string)$this->upload_id."' type='file' id='http_upload' name='http_upload' accept='".implode(",",$this->accepted_datatypes)."'></div><!--hidden input for non html5 functionality--><input type='hidden' name='file_no' value='".(string)$this->upload_id."'/><input type='hidden' name='csrf_token' value='".$this->csrf_token."'><div class='six columns'><button id='http_upload_button' upload_button_id='".(string)$this->upload_id."'>Click here to upload!</button></div></div><div class='row'><div class='twelve columns' id='input_validate_area' style='background-color:#ff4d4d;'></div></div></form><div id='post_upload_area' class='row' style='display:none'><div class='ten columns'><progress value='0' max='100' style='width:100%;height:40px;'></progress></div><div class='two columns'><button style='background-color:#ff3333' id='abort_upload_button'><font color='white'>X</font></button></div></div></div>";
		return $to_return;// return val here¬
	}
	/**
	 * Original code adapted from  <a href='http://php.net/manual/en/features.file-upload.php'>here</a>
	 */
	public function receive_upload(){
		/**
		 *
		 * Taken from <a href='http://php.net/manual/de/function.filesize.php'>http://php.net/manual/de/function.filesize.php</a> To format bytes in a human readable format
		 *
		 *
		 */
		function formatBytes($bytes, $precision = 2) {//define dependencies
			$units = array('B', 'KB', 'MB', 'GB', 'TB');
		
			$bytes = max($bytes, 0);
			$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
			$pow = min($pow, count($units) - 1);
		
			// Uncomment one of the following alternatives
			$bytes /= pow(1024, $pow);
			// $bytes /= (1 << (10 * $pow));
		
			return round($bytes, $precision) . ' ' . $units[$pow];
		}
		/**
		 * Attempts to format a mime datatype into a more human readable format;
		 * @param string $file_type MIME datatype
		 * @return string formated MIME filetype
		 */
		
		function format_filetype($file_type){
			if(strpos($file_type,"/")!==false){
				$file_type=array_pop(explode("/",$file_type));//split to get last part of string :D (array pop returns last item of string)
			}
			return $file_type;
		}
		/**
		 * This will delete all previous entries of uploads at a given directory under a set of mime types. This is so uploads retain 1 file per upload space as uploaded files for a single perpose can be of multiple datatypes.
		 * @param string $path the directory of the file
		 * @param string $filename dictated file name of upload
		 * @param string[] $mime_types The various mime types that the file could be E.G array("image/png","image/gif") ect...
		 */
		function clear_upload_files($path,$filename,$mime_types){
			$files=glob(rtrim($path,"/")."/".$filename.".*");
			foreach($files as $file){//for evrey file of a given name
				if (in_array(mime_content_type($file),$mime_types)){
					unlink($file);
				}
			}
			
		}
		//if(!headers_sent()){
		//	header('Content-Type: application/json');
		//}
		//$message=array("valid"=>false,"message"=>"");
		if (!isset($_FILES['http_upload']['error']) ||is_array($_FILES['http_upload']['error'])) {
			deploy_validation_response("Error: Please select a file. (Or the request was malformed)");
			
		};
		if(session_status() == PHP_SESSION_NONE){
			if(!session_start()){
				deploy_validation_response("Error: Session unable to start check that cookies are enabled.");
			}
		}
		
		if(!isset($_POST['file_no'])){
			deploy_validation_response("Error: Unable to link upload back to upload handle please refresh the page to refresh the webpage.");
		}
		if($_POST["csrf_token"]!=$this->csrf_token){
			deploy_validation_response("Error:  CSRF validation failed");
		}
		switch ($_FILES['http_upload']['error']) {
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_NO_FILE:
				deploy_validation_response("Error: no file given.");
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				deploy_validation_response("Error: max file size exceeded.");
			default:
				deploy_validation_response("Error: Unknown error.");
		}
		if ($_FILES['http_upload']['size'] > $this->upload_limit) {
			deploy_validation_response("Error: Maximum set filesize has been exceeded please upload a file of ".formatBytes($this->upload_limit).", or less, in size.");

		}
		$finfo = new \finfo(FILEINFO_MIME_TYPE);
		$file_mime_type=$finfo->file($_FILES['http_upload']['tmp_name']);
		
		if (array_search($file_mime_type,$this->accepted_datatypes)===False){
			deploy_validation_response("Error: file type '$file_mime_type' is invalid please upload one of the following filetypes: ".implode(", ",array_map(
			function ($file_type){
				if(strpos($file_type,"/")!==false){
					$file_type=explode("/",$file_type)[1];
				}
			return $file_type;
		},$this->accepted_datatypes)));
		}
		$path=$this->upload_dir;
		if(!is_dir($path)){
			
			mkdir($path, 0755, true);
		}
		$path_base=$path;
		$path.= $this->file_name.".".explode(".",$_FILES["http_upload"]["name"])[1];
		if(move_uploaded_file($_FILES["http_upload"]["tmp_name"], $path)){
			$_SESSION["upload_handles"][$_POST["file_no"]]="";//lockout upload handler after it has served its perpose
			clear_upload_files($path_base, $this->file_name,array_diff($this->accepted_datatypes,array($file_mime_type)));	
			deploy_validation_response("Upload of ".$_FILES["http_upload"]["name"]." sucsessfull!",true);		
		}
		else{
			deploy_validation_response("File was never moved to proper directory!");
		}
		
	
	}
	private static function get_upload_id(){
		self::$uploader_id++;
		return self::$uploader_id;
		//return "file_".(string)upload::$uploader_id;
	}
}
/**
 * Call when upload requests need to be handled
 */
function handle_upload(){
	if(!DEBUGGING){
		error_reporting(0);//turn off error reporting at this point
	}
	if(session_status() == PHP_SESSION_NONE){//call handle session so session data on file handles can be obtained can be retrieved.
		if(!session_start()){
			deploy_validation_response("Error: Session unable to start check that cookies are enabled.");
		}
	}
	if(!isset($_POST["file_no"])||!isset($_SESSION["upload_handles"])){//check for reqired superglobas being set
		deploy_validation_response("Error: File upload handle not found.");//send error message if invalid
	}
	if(!isset($_POST["csrf_token"])){
		deploy_validation_response("Error: CSRF token missing.");
	}
	if(!is_numeric($_POST["file_no"])){
		deploy_validation_response("Error: File number must be an intiger.");//send error message if invalid
	}
	
	if(!isset($_SESSION["upload_handles"][$_POST["file_no"]])){
		deploy_validation_response("Error: File upload handle not found.");//send error message if invalid
	}
	if($_SESSION["upload_handles"][$_POST["file_no"]]==""){
		deploy_validation_response("Error: There has allready been a file uploaded through this upload port. Please refresh the page if you wish to upload again.");
	}
	@$file_handle=unserialize($_SESSION["upload_handles"][(string)$_POST["file_no"]]);
	if(!$file_handle){
		deploy_validation_response("Error: File handle currupt or missing please refresh the page.");
	}
	if(DEBUGGING){
		set_error_handler(function($errno, $errstr, $errfile, $errline, array $errcontext) {
			deploy_validation_response("Debug data:<br/>Error: $errstr<br/>Error code: $errno<br/>Error file: $errfile on line $errline<br/>");
		});
	}
	$file_handle->receive_upload();
	if(DEBUGGING){
		restore_error_handler();
	}
};
/**
* This function deploys vaidation responses to AJAX calls made by the client side form.
* @param string $message The validation response to send.
* @param bool $valid if the response is valid or not.
*/
function deploy_validation_response($message,$valid=false){
	if(!isset($_POST["js_enabled"])){//if js not enabled
		$colour=$valid?"#33cc33":"#ff4d4d";
		echo "
		<html>
			<head>
			</head>
			<body>
				<h1>Upload status:</h1>
				<hr/>
				<p style='background-color:$colour'>
					$message
				</p>
				".
				(isset($_SERVER["HTTP_REFERER"])?"<a href='".$_SERVER["HTTP_REFERER"]."'>click here</a> to go back.":"")
				."
			</body>
		</html>
		";
		exit();
	}
	header('Content-Type: application/json');
	echo json_encode(array("message"=>$message,"valid"=>$valid));
	exit();
}
