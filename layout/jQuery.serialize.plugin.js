/*

	Copyright (C) 2008  United Nations University

	This programme is free software developed by Oleg Butuzov for  
	the United Nations University: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as  
	published by  the Free Software Foundation, version 3 of the 
	License, or (at your option) any later version.

	This program is distributed in the hope that it will be useful,  
	but WITHOUT ANY WARRANTY; without even the implied warranty of   
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the  GNU  
	General Public License for more details.

	 You should have received a copy of the GNU General Public License  
	 along with this program.  If not, see  http://www.gnu.org/licenses/.
*/

jQuery.fn.extend({
 WP_MultilingialSerialize: function() {
	  var obj = new Object();
		
	jQuery(this).find("input[@type='text'],input[@type='password'],input[@type='hidden'],textarea").each(function(){
		if (jQuery(this).attr('disabled') == false || jQuery(this).attr('disabled') == undefined){
		var k = this.name || this.id || this.parentNode.name || this.parentNode.id;
		obj[k] = jQuery(this).val();				
	}});
	
	jQuery(this).find("input[@type='checkbox']").filter(":checked").each(function(){
		if (jQuery(this).attr('disabled') == false  || jQuery(this).attr('disabled') == undefined){
		var k = this.name || this.id || this.parentNode.name || this.parentNode.id;
		obj[k] = jQuery(this).val();				
	}});
	
	jQuery(this).find("input[@type='radio']").filter(":checked").each(function(){
		if (jQuery(this).attr('disabled') == false  || jQuery(this).attr('disabled') == undefined){
		var k = this.name || this.id || this.parentNode.name || this.parentNode.id;
		obj[k] = jQuery(this).val();				
	}});	
	  
	jQuery(this).find("select").each(function(){
		if (jQuery(this).attr('disabled') == false  || jQuery(this).attr('disabled') == undefined){
		var k = this.name || this.id || this.parentNode.name || this.parentNode.id;
		obj[k] = jQuery(this).val();				
	}});  
	  
	return obj;
 }
});