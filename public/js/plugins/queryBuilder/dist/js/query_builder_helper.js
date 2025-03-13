// dynamic_dashboard
// filter
// auto_ticket
// alarm_notification
// log
// alarm

const get_labels = label_param => {
	return new Promise((resolve, reject) => 
	{
		var ids = [];
		if ( label_param.val instanceof Array && label_param.val.length > 0 )
		{
			ids = label_param.val;
		}
		else if ( typeof label_param.val != 'undefined' && label_param.val != null && label_param.val != "" )
		{
			ids.push(label_param.val)
		}

		if( ids.length > 0 )
		{
			$.post( option_url, { type:label_param.type, ids: JSON.stringify(ids) } )
			.done(function( resp_data )
			{
				resolve(JSON.parse(resp_data));
			}).fail(function() 
			{
				alert("Error Getting Lookup. Kindly contact Administrator.");
				resolve([]);
			});
		}
		else
		{
			resolve([]);
		}
	})
}

const value_validation = function (value, rule)
{
	if (value instanceof Array)
	{
		if( value.length > 0 )
		{
			for (var j = 0; j < value.length; j++)
			{
				if( typeof value[j] == 'undefined' || value[j] == null || value[j].trim() == "")
				{
					return ['{0} cannot be empty.', "Value"];
				}
			}
		}
		else
		{
			return ['{0} cannot be empty.', "Value"];
		}
	}
	else if ( typeof value == 'undefined' || value == null || value == "" )
	{
		return ['{0} cannot be empty.', "Value"];
	}
	return true;
}

const value_1setter = function (rule, value)
{
	var input = rule.$el.find('.rule-value-container [name*=_value_]')[0];
	$(input).val(null);
	$(input).val(value);
	$(input).trigger('chosen:updated');
}

const value_2setter = function (rule, value)
{
	var input = rule.$el.find('.rule-value-container [name*=_value_]')[0];
	$(input).val(null);
	$(input).val(value);
}

const value_3setter = function(rule, value) 
{
	var input = rule.$el.find('.rule-value-container [name*=_value_]')[0];

	if ( input.nodeName == "SELECT" )
	{
		if ( value instanceof Array && value.length > 0 )
		{
			for (var i = 0; i < value.length; i++)
			{
				var option = new Option(value[i], value[i], true, true);
				$(input).append(option);
			}
			$(input).val(value);
			$(input).trigger('change');
		}
		else if ( typeof value != 'undefined' && value != null && value != "" )
		{
			var option = new Option(value, value, true, true);
			$(input).append(option);
			$(input).val(value);
			$(input).trigger('change');
		}
	}
	else
	{
		$(input).val(null);
		$(input).val(value);
		$(input).trigger('change');
	}
}

const value_4setter = function(rule, value) 
{
	var input = rule.$el.find('.rule-value-container [name*=_value_]')[0];

	// loading is on main page here for standardization
	if ( input.nodeName == "TEXTAREA" )
	{
		$('#ibox1').children('.ibox-content').addClass('sk-loading');
		var opts = get_labels({val:value, type:rule.__.filter.id}).then(
			function (opts)
			{
				var str_value = [];
				$(opts).each(function(index, item)
				{
					str_value.push(item.text);
				});
				$(input).val(str_value.join("\n"));
				$(input).trigger('change');
				$('#ibox1').children('.ibox-content').removeClass('sk-loading');
			}
		);
	}
	else if ( input.nodeName == "SELECT" )
	{
		$('#ibox1').children('.ibox-content').addClass('sk-loading');
		var opts = get_labels({val:value, type:rule.__.filter.id}).then(
			function (opts)
			{
				$(opts).each(function(index, item)
				{
					var option = new Option(item.text, item.id, true, true);
					$(input).append(option);
				});
				$(input).val(value);
				$(input).trigger('change');
				$('#ibox1').children('.ibox-content').removeClass('sk-loading');
			}
		);
	}
	else
	{
		$(input).val(null);
		$(input).val(value);
		$(input).trigger('change');
	}
}

const value_5setter = function(rule, value) 
{
	if ( Array.isArray(value) )
	{
		if ( typeof value[0] != 'undefined' && value[0].trim() != "" )
		{
			var input1 = rule.$el.find('.rule-value-container [name*=_value_0]')[0];

			$(input1).val(null);
			$(input1).val(value[0]);
		}

		if ( typeof value[1] != 'undefined' && value[1].trim() != "" )
		{
			var input2 = rule.$el.find('.rule-value-container [name*=_value_1]')[0];

			$(input2).val(null);
			$(input2).val(value[1]);
		}
	}
	else
	{
		if ( typeof value != 'undefined' )
		{
			var input1 = rule.$el.find('.rule-value-container [name*=_value_0]')[0];

			$(input1).val(null);
			$(input1).val(value);
		}
	}
}

function getRENLookup()
{
	if( qb_setting == "filter" )
	{
		if( filter_domain_id != null )
		{
			if ( typeof domain_lookup["emses"][filter_domain_id+"_dom"] != 'undefined' )
			{
				return domain_lookup["emses"][filter_domain_id+"_dom"];
			}
		}
		else
		{
			return [];
		}
	}
	else
	{
		return domain_lookup["emses"];
	}
}

function getCellLookup()
{
	return domain_lookup["cell_parts"];
}

function getSeverityLookup()
{
	return domain_lookup["severity"];
}

function getVendorLookup()
{
	return domain_lookup["vendor"];
}

function getATLookup()
{
	return domain_lookup["alarm_type"];
}

function getDomainLookup()
{
	return domain_lookup["domain"];
}

// Site Lookup Stuff
function get_site_lookup(type, lookup)
{
	if ( typeof site_lookup[type] != 'undefined' && typeof site_lookup[type][lookup] != 'undefined' )
	{
		return site_lookup[type][lookup];
	}
	else
	{
		alert("Relevant Lookup not Found. Kindly Contact Administrator");
		return [];
	}
}

function value_field_maker_bts(e, rule)
{
	var ruleValueContainer = rule.$el.find('.rule-value-container');
	var input = rule.$el.find('.rule-value-container [name*=_value_0]')[0];
	var input_name = $(input).attr("name");

	if( 
		rule.__.filter.id == "site_priority" ||
		rule.__.filter.id == "share_type" ||
		rule.__.filter.id == "shared_operator" ||
		rule.__.filter.id == "regional_office" ||
		rule.__.filter.id == "om_office_type" ||
		rule.__.filter.id == "om_office" ||
		rule.__.filter.id == "bts_type" ||
		rule.__.filter.id == "vendor_2g" ||
		rule.__.filter.id == "vendor_3g" ||
		rule.__.filter.id == "vendor_4g" ||
		rule.__.filter.id == "zone" ||
		rule.__.filter.id == "district" ||
		rule.__.filter.id == "thana" ||
		rule.__.filter.id == "dg_combination" ||
		rule.__.filter.id == "site_type"
		 
	)
	{
		var ent_lookup = [];
		if( rule.__.filter.id == "site_priority" )	ent_lookup = get_site_lookup("bts_lookup", "site_priorityl");
		else if ( rule.__.filter.id == "share_type" )	ent_lookup = get_site_lookup("bts_lookup", "share_typel");
		else if ( rule.__.filter.id == "shared_operator" )	ent_lookup = get_site_lookup("bts_lookup", "shared_operatorl");
		else if ( rule.__.filter.id == "regional_office" )	ent_lookup = get_site_lookup("general", "ros");
		else if ( rule.__.filter.id == "om_office_type" )	ent_lookup = get_site_lookup("general", "om_type");
		else if ( rule.__.filter.id == "om_office" )	ent_lookup = get_site_lookup("general", "om_lookup");
		else if ( rule.__.filter.id == "bts_type" )	ent_lookup = get_site_lookup("bts_lookup", "bts_typel");
		else if ( rule.__.filter.id == "vendor_2g" )	ent_lookup = get_site_lookup("bts_lookup", "vendor_2gl");
		else if ( rule.__.filter.id == "vendor_3g" )	ent_lookup = get_site_lookup("bts_lookup", "vendor_2gl");
		else if ( rule.__.filter.id == "vendor_4g" )	ent_lookup = get_site_lookup("bts_lookup", "vendor_2gl");
		else if ( rule.__.filter.id == "zone" )	ent_lookup = get_site_lookup("general", "zones");
		else if ( rule.__.filter.id == "district" )	ent_lookup = get_site_lookup("bts_lookup", "districtl");
		else if ( rule.__.filter.id == "thana" )	ent_lookup = get_site_lookup("bts_lookup", "thanal");
		else if ( rule.__.filter.id == "dg_combination" )	ent_lookup = get_site_lookup("bts_lookup", "dg_combinationl");
		else if ( rule.__.filter.id == "site_type" )	ent_lookup = get_site_lookup("bts_lookup", "site_typel");

		if( rule.operator.type == "equal_to" )
		{

			var sel = $("<select name='"+input_name+"'>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
	}
	else if (
		rule.__.filter.id == "shared_site_code" ||
		rule.__.filter.id == "bsc_2g" ||
		rule.__.filter.id == "rnc_3g" ||
		rule.__.filter.id == "type_of_site" ||
		rule.__.filter.id == "type_of_bts" ||
		rule.__.filter.id == "msc_name" ||
		rule.__.filter.id == "corresponding_msc" ||
		rule.__.filter.id == "bts_model" ||
		rule.__.filter.id == "power_status" ||
		rule.__.filter.id == "vip_tag" ||
		rule.__.filter.id == "site_type_purpose" ||
		rule.__.filter.id == "hub_site"
		)
	{
		if( rule.operator.type == "equal_to" || rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
	else if (
		rule.__.filter.id == "generic_id" ||
		rule.__.filter.id == "site_code_2g" ||
		rule.__.filter.id == "site_code_3g" ||
		rule.__.filter.id == "site_code_4g"
		)
	{
		if( rule.operator.type == "equal_to" )
		{
			var sel = $("<select name='"+input_name+"'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple='multiple'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
		else if( rule.operator.type == "is_not_null" )
		{
			$(input).remove();
			$(ruleValueContainer).html('');
		}
	}	  	
}

function value_field_maker_bsc(e, rule)
{
	var ruleValueContainer = rule.$el.find('.rule-value-container');
	var input = rule.$el.find('.rule-value-container [name*=_value_0]')[0];
	var input_name = $(input).attr("name");

	if( 
		rule.__.filter.id == "type" ||
		rule.__.filter.id == "om_office_type" ||
		rule.__.filter.id == "om_office" ||
		rule.__.filter.id == "regional_office" ||
		rule.__.filter.id == "zone" ||
		rule.__.filter.id == "district" ||
		rule.__.filter.id == "thana" ||
		rule.__.filter.id == "vendor" 
	)
	{
		var ent_lookup = [];
		if( rule.__.filter.id == "type" )	ent_lookup = get_site_lookup("bsc_lookup", "typel");
		else if ( rule.__.filter.id == "om_office_type" )	ent_lookup = get_site_lookup("general", "om_type");
		else if ( rule.__.filter.id == "om_office" )	ent_lookup = get_site_lookup("general", "om_lookup");
		else if ( rule.__.filter.id == "regional_office" )	ent_lookup = get_site_lookup("general", "ros");
		else if ( rule.__.filter.id == "zone" )	ent_lookup = get_site_lookup("general", "zones");
		else if ( rule.__.filter.id == "district" )	ent_lookup = get_site_lookup("bsc_lookup", "districtl");
		else if ( rule.__.filter.id == "thana" )	ent_lookup = get_site_lookup("bsc_lookup", "thanal");
		else if ( rule.__.filter.id == "vendor" )	ent_lookup = get_site_lookup("bsc_lookup", "vendorl");

		if( rule.operator.type == "equal_to" )
		{

			var sel = $("<select name='"+input_name+"'>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
	}
	else if (
		rule.__.filter.id == "name_in_msc" ||
		rule.__.filter.id == "site_code" ||
		rule.__.filter.id == "switch"
		)
	{
		if( rule.operator.type == "equal_to" || rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
	else if (
		rule.__.filter.id == "bsc_name"
		)
	{
		if( rule.operator.type == "equal_to" )
		{
			var sel = $("<select name='"+input_name+"'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple='multiple'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
}

function value_field_maker_core(e, rule)
{
	var ruleValueContainer = rule.$el.find('.rule-value-container');
	var input = rule.$el.find('.rule-value-container [name*=_value_0]')[0];
	var input_name = $(input).attr("name");

	if( 
		rule.__.filter.id == "oem_vendor" ||
		rule.__.filter.id == "division" ||
		rule.__.filter.id == "district" ||
		rule.__.filter.id == "om_office_type" ||
		rule.__.filter.id == "om_office" ||
		rule.__.filter.id == "thana"
	)
	{
		var ent_lookup = [];
		if( rule.__.filter.id == "oem_vendor" )	ent_lookup = get_site_lookup("core_lookup", "oem_vendorl");
		else if ( rule.__.filter.id == "division" )	ent_lookup = get_site_lookup("core_lookup", "divisionl");
		else if ( rule.__.filter.id == "district" )	ent_lookup = get_site_lookup("core_lookup", "districtl");
		else if ( rule.__.filter.id == "thana" )	ent_lookup = get_site_lookup("core_lookup", "thanal");
		else if ( rule.__.filter.id == "om_office_type" )	ent_lookup = get_site_lookup("general", "om_type");
		else if ( rule.__.filter.id == "om_office" )	ent_lookup = get_site_lookup("general", "om_lookup");

		if( rule.operator.type == "equal_to" )
		{

			var sel = $("<select name='"+input_name+"'>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
	}
	else if (
		rule.__.filter.id == "site_name"
		)
	{
		if( rule.operator.type == "equal_to" || rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
	else if (
		rule.__.filter.id == "core_name"
		)
	{
		if( rule.operator.type == "equal_to" )
		{
			var sel = $("<select name='"+input_name+"'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple='multiple'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
}

function value_field_maker_mw_tx(e, rule)
{
	var ruleValueContainer = rule.$el.find('.rule-value-container');
	var input = rule.$el.find('.rule-value-container [name*=_value_0]')[0];
	var input_name = $(input).attr("name");

	if( 
		rule.__.filter.id == "regional_office" ||
		rule.__.filter.id == "zone" ||
		rule.__.filter.id == "division" ||
		rule.__.filter.id == "om_office_type" ||
		rule.__.filter.id == "om_office"
	)
	{
		var ent_lookup = [];
		if ( rule.__.filter.id == "regional_office" )	ent_lookup = get_site_lookup("general", "ros");
		else if ( rule.__.filter.id == "zone" )	ent_lookup = get_site_lookup("general", "zones");
		else if ( rule.__.filter.id == "division" )	ent_lookup = get_site_lookup("mwtx_lookup", "divisionl");
		else if ( rule.__.filter.id == "om_office_type" )	ent_lookup = get_site_lookup("general", "om_type");
		else if ( rule.__.filter.id == "om_office" )	ent_lookup = get_site_lookup("general", "om_lookup");

		if( rule.operator.type == "equal_to" )
		{

			var sel = $("<select name='"+input_name+"'>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
	}
	else if (
		rule.__.filter.id == "site_type_purpose" ||
		rule.__.filter.id == "node" ||
		rule.__.filter.id == "co_located_bts"
		)
	{
		if( rule.operator.type == "equal_to" || rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
	else if (
		rule.__.filter.id == "mw_site_code"
		)
	{
		if( rule.operator.type == "equal_to" )
		{
			var sel = $("<select name='"+input_name+"'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple='multiple'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
}

function value_field_maker_of_tx(e, rule)
{
	var ruleValueContainer = rule.$el.find('.rule-value-container');
	var input = rule.$el.find('.rule-value-container [name*=_value_0]')[0];
	var input_name = $(input).attr("name");

	if(
		rule.__.filter.id == "regional_office" ||
		rule.__.filter.id == "zone" ||
		rule.__.filter.id == "division" ||
		rule.__.filter.id == "district" ||
		rule.__.filter.id == "thana" ||
		rule.__.filter.id == "om_office_type" ||
		rule.__.filter.id == "om_office"
	)
	{
		var ent_lookup = [];
		if( rule.__.filter.id == "site_type_purpose" )	ent_lookup = get_site_lookup("oftx_lookup", "site_type_purposel");
		else if ( rule.__.filter.id == "regional_office" )	ent_lookup = get_site_lookup("general", "ros");
		else if ( rule.__.filter.id == "zone" )	ent_lookup = get_site_lookup("general", "zones");
		else if ( rule.__.filter.id == "division" )	ent_lookup = get_site_lookup("oftx_lookup", "divisionl");
		else if ( rule.__.filter.id == "district" )	ent_lookup = get_site_lookup("oftx_lookup", "districtl");
		else if ( rule.__.filter.id == "thana" )	ent_lookup = get_site_lookup("oftx_lookup", "thanal");
		else if ( rule.__.filter.id == "om_office_type" )	ent_lookup = get_site_lookup("general", "om_type");
		else if ( rule.__.filter.id == "om_office" )	ent_lookup = get_site_lookup("general", "om_lookup");

		if( rule.operator.type == "equal_to" )
		{

			var sel = $("<select name='"+input_name+"'>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
	}
	else if (
		rule.__.filter.id == "co_located_bts" ||
		rule.__.filter.id == "site_type_purpose" ||
		rule.__.filter.id == "node"
		)
	{
		if( rule.operator.type == "equal_to" || rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
	else if (
		rule.__.filter.id == "of_site_code"
		)
	{
		if( rule.operator.type == "equal_to" )
		{
			var sel = $("<select name='"+input_name+"'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple='multiple'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
}

function value_field_maker_datacom(e, rule)
{
	var ruleValueContainer = rule.$el.find('.rule-value-container');
	var input = rule.$el.find('.rule-value-container [name*=_value_0]')[0];
	var input_name = $(input).attr("name");

	if( 
		rule.__.filter.id == "vendor" ||
		rule.__.filter.id == "om_office_type" ||
		rule.__.filter.id == "om_office"
	)
	{
		var ent_lookup = [];
		if ( rule.__.filter.id == "vendor" )	ent_lookup = get_site_lookup("datacom_lookup", "vendorl");
		else if ( rule.__.filter.id == "om_office_type" )	ent_lookup = get_site_lookup("general", "om_type");
		else if ( rule.__.filter.id == "om_office" )	ent_lookup = get_site_lookup("general", "om_lookup");

		if( rule.operator.type == "equal_to" )
		{

			var sel = $("<select name='"+input_name+"'>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
	}
	else if (
		rule.__.filter.id == "ne_type" ||
		rule.__.filter.id == "ne_id" ||
		rule.__.filter.id == "city" ||
		rule.__.filter.id == "model" 
		)
	{
		if( rule.operator.type == "equal_to" || rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
	else if (
		rule.__.filter.id == "datacom_name"
		)
	{
		if( rule.operator.type == "equal_to" )
		{
			var sel = $("<select name='"+input_name+"'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple='multiple'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
}

function value_field_maker_itsm(e, rule)
{
	var ruleValueContainer = rule.$el.find('.rule-value-container');
	var input = rule.$el.find('.rule-value-container [name*=_value_0]')[0];
	var input_name = $(input).attr("name");

	if( 
		rule.__.filter.id == "type" ||
		rule.__.filter.id == "tier" ||
		rule.__.filter.id == "om_office_type" ||
		rule.__.filter.id == "om_office" ||
		rule.__.filter.id == "creation_type"
	)
	{
		var ent_lookup = [];
		if ( rule.__.filter.id == "type" )	ent_lookup = get_site_lookup("itsm_lookup", "typel");
		if ( rule.__.filter.id == "tier" )	ent_lookup = get_site_lookup("itsm_lookup", "tierl");
		else if ( rule.__.filter.id == "creation_type" )	ent_lookup = get_site_lookup("itsm_lookup", "creation_typel");
		else if ( rule.__.filter.id == "om_office_type" )	ent_lookup = get_site_lookup("general", "om_type");
		else if ( rule.__.filter.id == "om_office" )	ent_lookup = get_site_lookup("general", "om_lookup");

		if( rule.operator.type == "equal_to" )
		{

			var sel = $("<select name='"+input_name+"'>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple>");
			$(ent_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
	}
	else if (
		rule.__.filter.id == "bl_sme" ||
		rule.__.filter.id == "description" ||
		rule.__.filter.id == "oem" ||
		rule.__.filter.id == "model" ||
		rule.__.filter.id == "service_name" ||
		rule.__.filter.id == "operating_system"
		)
	{
		if( rule.operator.type == "equal_to" || rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
	else if (
		rule.__.filter.id == "itsm_host_name" ||
		rule.__.filter.id == "itsm_ip_address"
		)
	{
		if( rule.operator.type == "equal_to" )
		{
			var sel = $("<select name='"+input_name+"'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple='multiple'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id);
		}
		else if( rule.operator.type == "starts_with" || rule.operator.type == "is_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
}

function qb_bts()
{
	$('#builder-widgets-sites').queryBuilder({
		allow_empty: true,

		operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat([
			{ type: 'equal_to',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_one_of',  nb_inputs: 1, multiple: true, apply_to: ['string'] },
			{ type: 'starts_with',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_not_null_custom',  nb_inputs: 0, multiple: false, apply_to: ['string'] },
		]),

		lang: {
			operators: {
				equal_to: 'Equal to',
				is_one_of: 'Is one of',
				starts_with: 'Starts with',
				is_contains: 'Contains',
				is_not_null_custom: 'Is NOT NULL',
			}
		},

		filters:
		[
		{
			id: "generic_id",
			label: "Generic ID",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_contains',
				'is_not_null_custom'
			],
			valueSetter: value_3setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "site_code_2g",
			label: "2G Site Code",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_contains',
				'is_not_null_custom'
			],
			valueSetter: value_3setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "site_code_3g",
			label: "3G Site Code",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_contains',
				'is_not_null_custom'
			],
			valueSetter: value_3setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "site_code_4g",
			label: "4G Site Code",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_contains',
				'is_not_null_custom'
			],
			valueSetter: value_3setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "site_priority",
			label: "Site Priority",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "share_type",
			label: "Shared Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "shared_operator",
			label: "Shared Operator",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "regional_office",
			label: "Regional Office",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office_type",
			label: "O&M office Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office",
			label: "O&M office",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "bts_type",
			label: "BTS Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "vendor_2g",
			label: "2G Vendor",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "vendor_3g",
			label: "3G Vendor",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "vendor_4g",
			label: "4G Vendor",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "zone",
			label: "Zone",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "district",
			label: "District",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "thana",
			label: "Thana",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "type_of_site",
			label: "Type of Site",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "type_of_bts",
			label: "Type of BTS",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "dg_combination",
			label: "DG Combination",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "site_type",
			label: "Site Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "site_type_purpose",
			label: "Site Type/Purpose",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},


		{
			id: "shared_site_code",
			label: "Shared Site Code",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "bsc_2g",
			label: "2G BSC",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "rnc_3g",
			label: "3G RNC",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "msc_name",
			label: "Name in MSC",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "corresponding_msc",
			label: "Corresponding MSC",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "bts_model",
			label: "BTS Model",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "power_status",
			label: "Power Status",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "vip_tag",
			label: "VIP Tag",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "hub_site",
			label: "Hub Site",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		],
		rules: []
	});
}

function qb_bsc()
{
	$('#builder-widgets-sites').queryBuilder({
		allow_empty: true,

		operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat([
			{ type: 'equal_to',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_one_of',  nb_inputs: 1, multiple: true, apply_to: ['string'] },
			{ type: 'starts_with',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },

		]),

		lang: {
			operators: {
				equal_to: 'Equal to',
				is_one_of: 'Is one of',
				starts_with: 'Starts with',
				is_contains: 'Contains',
			}
		},

		filters:
		[
		{
			id: "bsc_name",
			label: "Name",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_contains'
			],
			valueSetter: value_3setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "type",
			label: "Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office_type",
			label: "O&M Office Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office",
			label: "O&M Office",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "regional_office",
			label: "Regional Office",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "zone",
			label: "Zone",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "district",
			label: "District",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "thana",
			label: "Thana",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "vendor",
			label: "Vendor",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "name_in_msc",
			label: "Name in MSC",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "site_code",
			label: "Site Code",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "switch",
			label: "Switch",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		],
		rules: []
	});
}

function qb_core()
{
	$('#builder-widgets-sites').queryBuilder({
		allow_empty: true,

		operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat([
			{ type: 'equal_to',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_one_of',  nb_inputs: 1, multiple: true, apply_to: ['string'] },
			{ type: 'starts_with',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },

		]),

		lang: {
			operators: {
				equal_to: 'Equal to',
				is_one_of: 'Is one of',
				starts_with: 'Starts with',
				is_contains: 'Contains',
			}
		},

		filters:
		[
		{
			id: "core_name",
			label: "Name",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_contains'
			],
			valueSetter: value_3setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "oem_vendor",
			label: "OEM Vendor",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office_type",
			label: "O&M office Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office",
			label: "O&M office",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "division",
			label: "Division",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "district",
			label: "District",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "thana",
			label: "Thana",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "site_name",
			label: "Site Name",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		],
		rules: []
	});
}

function qb_mw_tx()
{
	$('#builder-widgets-sites').queryBuilder({
		allow_empty: true,

		operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat([
			{ type: 'equal_to',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_one_of',  nb_inputs: 1, multiple: true, apply_to: ['string'] },
			{ type: 'starts_with',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },

		]),

		lang: {
			operators: {
				equal_to: 'Equal to',
				is_one_of: 'Is one of',
				starts_with: 'Starts with',
				is_contains: 'Contains',
			}
		},

		filters:
		[
		{
			id: "mw_site_code",
			label: "Site Code",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_contains'
			],
			valueSetter: value_3setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "site_type_purpose",
			label: "Site Type/Purpose",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "node",
			label: "Node",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "regional_office",
			label: "Regional Office",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "zone",
			label: "Zone",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "division",
			label: "Division",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office_type",
			label: "O&M Office Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office",
			label: "O&M Office",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		

		{
			id: "co_located_bts",
			label: "Co-Located BTS",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		],
		rules: []
	});
}

function qb_of_tx()
{
	$('#builder-widgets-sites').queryBuilder({
		allow_empty: true,

		operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat([
			{ type: 'equal_to',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_one_of',  nb_inputs: 1, multiple: true, apply_to: ['string'] },
			{ type: 'starts_with',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },

		]),

		lang: {
			operators: {
				equal_to: 'Equal to',
				is_one_of: 'Is one of',
				starts_with: 'Starts with',
				is_contains: 'Contains',
			}
		},

		filters:
		[
		{
			id: "of_site_code",
			label: "Site Code",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_contains'
			],
			valueSetter: value_3setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "site_type_purpose",
			label: "Site Type/Purpose",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "regional_office",
			label: "Regional Office",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "zone",
			label: "Zone",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "division",
			label: "Division",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "district",
			label: "District",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "thana",
			label: "Thana",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office_type",
			label: "O&M Office Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office",
			label: "O&M Office",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "co_located_bts",
			label: "Co-Located BTS",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "node",
			label: "Node",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		],
		rules: []
	});
}

function qb_datacom()
{
	$('#builder-widgets-sites').queryBuilder({
		allow_empty: true,

		operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat([
			{ type: 'equal_to',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_one_of',  nb_inputs: 1, multiple: true, apply_to: ['string'] },
			{ type: 'starts_with',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },

		]),

		lang: {
			operators: {
				equal_to: 'Equal to',
				is_one_of: 'Is one of',
				starts_with: 'Starts with',
				is_contains: 'Contains',
			}
		},

		filters:
		[
		{
			id: "datacom_name",
			label: "Name",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_contains'
			],
			valueSetter: value_3setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "ne_type",
			label: "NE Type",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "vendor",
			label: "Vendor",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office_type",
			label: "O&M office Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office",
			label: "O&M office",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "city",
			label: "City",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "ne_id",
			label: "NE ID",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "model",
			label: "Model",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		],
		rules: []
	});
}

function qb_itsm()
{
	$('#builder-widgets-sites').queryBuilder({
		allow_empty: true,

		operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat([
			{ type: 'equal_to',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_one_of',  nb_inputs: 1, multiple: true, apply_to: ['string'] },
			{ type: 'starts_with',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },

		]),

		lang: {
			operators: {
				equal_to: 'Equal to',
				is_one_of: 'Is one of',
				starts_with: 'Starts with',
				is_contains: 'Contains',
			}
		},

		filters:
		[
		{
			id: "itsm_host_name",
			label: "Host Name",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_contains'
			],
			valueSetter: value_3setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "itsm_ip_address",
			label: "IP Address",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_contains'
			],
			valueSetter: value_3setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "type",
			label: "Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "tier",
			label: "Tier",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "creation_type",
			label: "Creation Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office_type",
			label: "O&M office Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "om_office",
			label: "O&M office",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},

		{
			id: "service_name",
			label: "Service Name",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "operating_system",
			label: "Operating System",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "bl_sme",
			label: "BL SME",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "description",
			label: "Description",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "oem",
			label: "OEM",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "model",
			label: "Model",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		],
		rules: []
	});
}



function select2_maker(elem, type, parent)
{
	$.fn.select2.amd.require(['select2/selection/search'], function (Search) {
		var oldRemoveChoice = Search.prototype.searchRemoveChoice;
		
		Search.prototype.searchRemoveChoice = function () {
			oldRemoveChoice.apply(this, arguments);
			this.$search.val('');
		};
		
		if( parent != null )
		{
			$(elem).select2({
				dropdownParent: parent,
				width: '100%',
				minimumInputLength: 1,
				closeOnSelect: false,
				ajax: {
					url: select_ad_url,
					dataType: 'json',
					type: "GET",
					data: function (params) {
						var query = {
							search: params.term,
							page: params.page || 1,
							type: type,
						}
						return query;
					},
					processResults: function (resp)
					{
						return {
							results: resp.data,
							pagination: {
								more: resp.more,
							}
						};
					}
				}
			});
		}
		else
		{
			$(elem).select2({
				width: '100%',
				minimumInputLength: 1,
				closeOnSelect: false,
				ajax: {
					url: select_ad_url,
					dataType: 'json',
					type: "GET",
					data: function (params) {
						var query = {
							search: params.term,
							page: params.page || 1,
							type: type,
						}
						return query;
					},
					processResults: function (resp)
					{
						return {
							results: resp.data,
							pagination: {
								more: resp.more,
							}
						};
					}
				}
			});
		}
	});
}

function value_field_maker(e, rule, parent_modal = null)
{
	var ruleValueContainer = rule.$el.find('.rule-value-container');
	var input = rule.$el.find('.rule-value-container [name*=_value_0]')[0];
	var input_name = $(input).attr("name");

	if( rule.__.filter.id == "perceived_severity" || rule.__.filter.id == "vendor" || rule.__.filter.id == "alarm_type" || rule.__.filter.id == "reporting_name" || rule.__.filter.id == "cell" || rule.__.filter.id == "domain" )
	{
		var attr_lookup = []
		if(rule.__.filter.id == "perceived_severity") attr_lookup = getSeverityLookup();
		else if(rule.__.filter.id == "domain") attr_lookup = getDomainLookup();
		else if(rule.__.filter.id == "vendor") attr_lookup = getVendorLookup();
		else if(rule.__.filter.id == "alarm_type") attr_lookup = getATLookup();
		else if(rule.__.filter.id == "reporting_name") attr_lookup = getRENLookup();
		else if(rule.__.filter.id == "cell") attr_lookup = getCellLookup();

		if( rule.operator.type == "equal_to" )
		{
			var sel = $("<select name='"+input_name+"'>");
			$(attr_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<select name='"+input_name+"' multiple>");
			$(attr_lookup).each(function(index, item) {
				sel.append($("<option>").attr('value',item.id).text(item.text));
			});

			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			$(sel).chosen({width: "100%",no_results_text: "No result found.",search_contains: true});
		}
	}
	else if( rule.__.filter.id == "specific_problem" || rule.__.filter.id == "probable_cause" || rule.__.filter.id == "source_system_name" || rule.__.filter.id == "source_system_id" )
	{
		if( rule.operator.type == "equal_to" )
		{
			var sel = $("<select name='"+input_name+"'>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
			select2_maker(sel, rule.__.filter.id, parent_modal);
		}
		else if( rule.operator.type == "is_one_of" )
		{
			if( qb_setting == "alarm" || qb_setting == "log" )
			{
				var sel = $("<select name='"+input_name+"' multiple='multiple'>");
				$.each($._data(input, "events"), function() {
				  $.each(this, function() {
					$(sel).bind(this.type, this.handler);
				  });
				});
				$(input).remove();
				$(ruleValueContainer).html('');
				sel.appendTo(ruleValueContainer);
				$(sel).change();
				select2_maker(sel, rule.__.filter.id);
			}
			else
			{
				var sel = $("<textarea class='form-control' style='width:100% !important;min-height: 150px;' name='"+input_name+"' readonly></textarea>");
				var btn = $('<button type="button" class="btn btn-primary blk_sel" onclick="event.stopPropagation();bulk_select(\''+rule.id+'\',\''+rule.__.filter.id+'\')" >Select Options</button>');
				$.each($._data(input, "events"), function() {
				  $.each(this, function() {
					$(sel).bind(this.type, this.handler);
				  });
				});
				$(input).remove();
				$(ruleValueContainer).html('');
				sel.appendTo(ruleValueContainer);
				btn.appendTo(ruleValueContainer);
				$(sel).change();
			}
		}
		else if( rule.operator.type == "starts_with" || rule.operator.type == "is_contains" || rule.operator.type == "is_not_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
	else if( rule.__.filter.id == "alarm_raised_time" || rule.__.filter.id == "alarm_changed_time" )
	{
		if( rule.operator.type == "last_x_min" || rule.operator.type == "last_x_hr" || rule.operator.type == "bfr_last_x_min" || rule.operator.type == "bfr_last_x_hr" )
		{
			var sel = $("<input type='number' name='"+input_name+"' min='0' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
	else if( rule.__.filter.id == "alarmed_object" || rule.__.filter.id == "alarmed_object_type" || rule.__.filter.id == "alarm_condition" || rule.__.filter.id == "proposed_repair_action" || rule.__.filter.id == "external_id" || rule.__.filter.id == "location_information" )
	{
		if( rule.operator.type == "equal_to" || rule.operator.type == "starts_with" || rule.operator.type == "is_contains" || rule.operator.type == "is_not_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
	}
	else if( rule.__.filter.id == "site_name" )
	{
		if( rule.operator.type == "equal_to" || rule.operator.type == "starts_with" || rule.operator.type == "is_contains" || rule.operator.type == "is_not_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
		else if( rule.operator.type == "is_one_of" )
		{
			var sel = $("<textarea class='form-control' placeholder='Add Items on New Line. Empty Lines will be omitted.' style='width:100% !important;' name='"+input_name+"'></textarea>");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).change();
		}
		else if ( rule.operator.type == "no_site_name" || rule.operator.type == "site_name_not_in_db" )
		{
			$(input).remove();
			$(ruleValueContainer).html('');
		}
	}
	else if( rule.__.filter.id == "alarm_details" )
	{
		if( rule.operator.type == "has_key" )
		{
		   var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});
			$(input).remove();
			$(ruleValueContainer).html('');
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");
		}
		else if( rule.operator.type == "key_value_equals" || rule.operator.type == "key_value_starts_with" || rule.operator.type == "key_value_contains" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm first_field' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});

			var input2 = rule.$el.find('.rule-value-container [name*=_value_1]')[0];
			var input_name2 = $(input2).attr("name");

			var sel2 = $("<input type='text' name='"+input_name2+"' value='' class='form-control form-control-sm' style='width: 100%;' >");
			$.each($._data(input2, "events"), function() {
			  $.each(this, function() {
				$(sel2).bind(this.type, this.handler);
			  });
			});

			$(input).remove();
			$(input2).remove();
			$(ruleValueContainer).html('');

			$(ruleValueContainer).append("<label class='qb_label'>Key:</label");
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");

			$(ruleValueContainer).append("<label class='qb_label'>Value:</label");
			sel2.appendTo(ruleValueContainer);
			$(sel2).val('');
			$(sel2).trigger("change");
		}
		else if ( rule.operator.type == "key_value_contains_multi" )
		{
			var sel = $("<input type='text' name='"+input_name+"' value='' class='form-control form-control-sm first_field' style='width: 100%;' >");
			$.each($._data(input, "events"), function() {
			  $.each(this, function() {
				$(sel).bind(this.type, this.handler);
			  });
			});

			var input2 = rule.$el.find('.rule-value-container [name*=_value_1]')[0];
			var input_name2 = $(input2).attr("name");

			var sel2 = $("<textarea name='"+input_name2+"' value='' placeholder='Add options separated by next Line' class='form-control form-control-sm' style='width: 100%;height: 140px;' >");
			$.each($._data(input2, "events"), function() {
			  $.each(this, function() {
				$(sel2).bind(this.type, this.handler);
			  });
			});

			$(input).remove();
			$(input2).remove();
			$(ruleValueContainer).html('');

			$(ruleValueContainer).append("<label class='qb_label'>Key:</label");
			sel.appendTo(ruleValueContainer);
			$(sel).val('');
			$(sel).trigger("change");

			$(ruleValueContainer).append("<label class='qb_label'>Value:</label");
			sel2.appendTo(ruleValueContainer);
			$(sel2).val('');
			$(sel2).trigger("change");
		}
	}
	else if( rule.__.filter.id == "ticket" || rule.__.filter.id == "ack_state" )
	{
		$(input).remove();
		$(ruleValueContainer).html('');
	}
}

function create_alarm_qb(elem_id, filters, conf_elem, after)
{
	// Fix for Selectize
	if( !after )
	{
		$('#'+elem_id).on('afterCreateRuleInput.queryBuilder', function(e, rule)
		{
			value_field_maker(e, rule, conf_elem);
		});

		$('#'+elem_id).on('afterUpdateRuleOperator.queryBuilder', function(e, rule)
		{
			value_field_maker(e, rule, conf_elem);
		});
	}

	// Value setter function issue calls multiple times(for single). first time calls with first value in dropdown. cannot be solved from epty first value(problem in multiselect)
	$('#'+elem_id).queryBuilder({
		// plugins: ['bt-tooltip-errors'],
		allow_empty: true,

		operators: $.fn.queryBuilder.constructor.DEFAULTS.operators.concat([
			{ type: 'equal_to',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'starts_with',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_one_of',  nb_inputs: 1, multiple: true, apply_to: ['string'] },
			{ type: 'is_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'is_not_contains',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'last_x_min',  nb_inputs: 1, multiple: false, apply_to: ['integer'] },
			{ type: 'last_x_hr',  nb_inputs: 1, multiple: false, apply_to: ['integer'] },
			{ type: 'bfr_last_x_min',  nb_inputs: 1, multiple: false, apply_to: ['integer'] },
			{ type: 'bfr_last_x_hr',  nb_inputs: 1, multiple: false, apply_to: ['integer'] },
			{ type: 'not_opened',  nb_inputs: 0, multiple: false, apply_to: ['string'] },
			{ type: 'opened',  nb_inputs: 0, multiple: false, apply_to: ['string'] },
			{ type: 'has_key',  nb_inputs: 1, multiple: false, apply_to: ['string'] },
			{ type: 'key_value_equals',  nb_inputs: 2, multiple: false, apply_to: ['string'] },
			{ type: 'key_value_starts_with',  nb_inputs: 2, multiple: false, apply_to: ['string'] },
			{ type: 'key_value_contains',  nb_inputs: 2, multiple: false, apply_to: ['string'] },
			{ type: 'key_value_contains_multi',  nb_inputs: 2, multiple: false, apply_to: ['string'] },
			{ type: 'no_site_name',  nb_inputs: 0, multiple: false, apply_to: ['string'] },
			{ type: 'site_name_not_in_db',  nb_inputs: 0, multiple: false, apply_to: ['string'] },
			{ type: 'acked',  nb_inputs: 0, multiple: false, apply_to: ['string'] },
			{ type: 'unacked',  nb_inputs: 0, multiple: false, apply_to: ['string'] },
			{ type: 'ack_not_found',  nb_inputs: 0, multiple: false, apply_to: ['string'] },
		]),

		lang: {
			operators: {
				equal_to: 'Equal to',
				is_one_of: 'Is one of',
				starts_with: 'Starts with',
				is_contains: 'Contains',
				is_not_contains: 'Does Not Contain',
				last_x_min: 'In Last X Minutes',
				last_x_hr: 'In Last X Hour',
				not_opened: 'Not Opened',
				opened: 'Opened',
				bfr_last_x_min: 'Before Last X Minutes',
				bfr_last_x_hr: 'Before Last X Hour',
				has_key: 'Has Key',
				key_value_equals: 'Key Value Equals',
				key_value_starts_with: 'Key Value Starts With',
				key_value_contains: 'Key Value Contains',
				key_value_contains_multi: 'Key Value Contains Multiple',
				no_site_name: 'Not Found from Alarm',
				site_name_not_in_db: 'Not Found in DB',
				acked: 'Acknowledged',
				unacked: 'Unacknowledged',
				ack_not_found: 'Not Found',
			}
		},

		filters:filters,
		rules: []
	});

	if( after )
	{
		$('#'+elem_id).on('afterCreateRuleInput.queryBuilder', function(e, rule)
		{
			value_field_maker(e, rule, conf_elem);
		});

		$('#'+elem_id).on('afterUpdateRuleOperator.queryBuilder', function(e, rule)
		{
			value_field_maker(e, rule, conf_elem);
		});
	}
}

function get_short_filters(skip = null)
{
	var filters = [
		{
			id: "perceived_severity",
			label: "Perceived Severity",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "domain",
			label: "Domain",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "specific_problem",
			label: "Alarm Slogan",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_not_contains',
				'is_contains'
			],
			valueSetter: value_4setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "probable_cause",
			label: "Probable Cause",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_not_contains',
				'is_contains'
			],
			valueSetter: value_4setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "vendor",
			label: "Vendor",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "alarm_type",
			label: "Alarm Type",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "reporting_name",
			label: "Element Manager",
			type: 'string',
			operators: [
				'equal_to',
				'is_one_of'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "source_system_name",
			label: "Source System Name",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_not_contains',
				'is_contains'
			],
			valueSetter: value_4setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "source_system_id",
			label: "Source System Id",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_not_contains',
				'is_contains'
			],
			valueSetter: value_4setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "alarm_raised_time",
			label: "Alarm Raised Time",
			type: 'integer',
			operators: [
				'last_x_min',
				'last_x_hr',
				'bfr_last_x_min',
				'bfr_last_x_hr'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "alarm_changed_time",
			label: "Alarm Changed Time",
			type: 'integer',
			operators: [
				'last_x_min',
				'last_x_hr',
				'bfr_last_x_min',
				'bfr_last_x_hr'
			],
			valueSetter: value_1setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "external_id",
			label: "Alarm ID",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_not_contains',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "site_name",
			label: "Site Name",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_one_of',
				'is_not_contains',
				'is_contains',
				'site_name_not_in_db',
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "location_information",
			label: "Location Information",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_not_contains',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "alarmed_object",
			label: "Alarmed Object",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_not_contains',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "alarmed_object_type",
			label: "Alarmed Object Type",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_not_contains',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "alarm_condition",
			label: "Alarm Condition",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_not_contains',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "proposed_repair_action",
			label: "Proposed Repair Actions",
			type: 'string',
			operators: [
				'equal_to',
				'starts_with',
				'is_not_contains',
				'is_contains'
			],
			valueSetter: value_2setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "alarm_details",
			label: "Non-TMF attributes",
			type: 'string',
			operators: [
				'has_key',
				'key_value_equals',
				'key_value_starts_with',
				'key_value_contains',
				'key_value_contains_multi'
			],
			valueSetter: value_5setter,
			validation: {
				callback: value_validation,
			},
		},
		{
			id: "ticket",
			label: "Ticket",
			type: "string",
			operators: [
				'not_opened',
				'opened'
			],
			valueSetter: function(rule, value) 
			{
			},
			validation: {
				callback: function(value, rule)
				{
					return true;
				}
			},
		},
		{
			id: "ack_state",
			label: "Ack State",
			type: "string",
			operators: [
				'acked',
				'unacked',
				'ack_not_found'
			],
			valueSetter: function(rule, value) 
			{
			},
			validation: {
				callback: function(value, rule)
				{
					return true;
				}
			},
		},
	];
	
	if( skip != null )
	{
		for (var i = 0; i < filters.length; i++)
		{
			if( filters[i].id == skip )
			{
				filters.splice(i, 1);
			}
		}
	}

	return filters;
}

var bulk_input = null;
var bulk_type = null;

function bulk_select(rule_id, type)
{
	var input = $("#"+rule_id+" .rule-value-container [name*=_value_0]")[0];

	if( typeof input != 'undefined' && input != null && ( type == "specific_problem" || type == "probable_cause" || type == "source_system_name" || type == "source_system_id" ) )
	{
		bulk_input = input;
		bulk_type = type;
		$('#bulk_options').val( $(input).val() );
		$('#bulk_edit_modal').modal({
		    backdrop: 'static',
		    keyboard: false
		});
	}
	else
	{
		alert("Element not Found. Kindly contact Administrator");
	}
}

function close_bulk_edit()
{
	bulk_input = null;
	bulk_type = null;
	$('#bulk_edit_modal').modal('hide');
	$('#bulk_options').val('');
}

function perform_bulk_edit(load_div)
{
    if( bulk_input != null && bulk_type != null )
    {
        var options = $('#bulk_options').val();
        if( options.trim() != "" )
        {
            $('#bulk_edit_modal').modal('hide');
            $('#'+load_div).children('.ibox-content').addClass('sk-loading');
            $.post( bulk_edit_url, { type: bulk_type, options: options } )
            .done(function( resp_data )
            {
                $('#'+load_div).children('.ibox-content').removeClass('sk-loading');

                var res_obj = JSON.parse(resp_data);

                $(bulk_input).empty().trigger('change');
                var sel_val = [];

                for (var i = 0; i < res_obj.options.length; i++)
                {
                    sel_val.push(res_obj.options[i].text);
                }

                $(bulk_input).val(sel_val.join('\n'));
                $(bulk_input).trigger('change');

                var html = ''+res_obj.options.length+' options are found and selected.';
                if( res_obj.not_found.length > 0)
                {
                    html += '<br/>Following '+res_obj.not_found.length+' options are not found in DB. kindly add these options and then try again';
                    html += '<ul style="text-align: left;">';
                    for (var i = 0; i < res_obj.not_found.length; i++)
                    {
                        html +=  '<li>'+res_obj.not_found[i]+'</li>';
                    }
                    html += '</ul>';
                }

                bulk_input = null;
                bulk_type = null;
                $('#bulk_options').val('');

                swal({
                    type: 'info',
                    title: 'Bulk Select Summary!',
                    html: html,
                });

            }).fail(function( http_res ) 
            {
                $('#'+load_div).children('.ibox-content').removeClass('sk-loading');
                swal({
                  type: 'error',
                  title: 'Error in perfoming Bulk Select!',
                  text: 'Error occured in perfoming Bulk Select. Try Refreshing the page. if Error persists, Kindly contact Administrator.',
                });
                console.log(http_res);
            });

        }
        else
        {
            swal({
              type: 'error',
              title: 'No Options Entered!',
              text: 'Please add Some Options in the textbox.',
            });
        }
    }
    else
    {
        alert("Input not Found! Kindly contact Administrator");
    }
}