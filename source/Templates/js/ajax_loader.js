/**
 * Only once the document is fully-loaded should the madness begin.
 */
$(document).ready(function () {
    loadAirports();
    loadCarriers();
    loadStatisticSelectOptions();
    loadStatistics();

    $('#airport_submit').click(loadAirports);
    $('#carrier_submit').click(loadCarriers);
    $('#statistics_submit').click(loadStatistics);

    $('#statistics_year_enabled').change(toggleYearEnabled);
    $('#statistics_month_enabled').change(toggleMonthEnabled);
});

/**
 * Loads data from some endpoint.
 * @param base_endpoint_uri
 * @param page
 * @param limit
 * @param container_id
 * @param template_id
 * @param additional_query_params
 */
function loadCollection(base_endpoint_uri, page, limit, container_id, template_id, additional_query_params = '') {
    let entity_container_element = $(container_id);
    entity_container_element.empty();
    $.ajax(base_endpoint_uri + '?page=' + page + '&limit=' + limit + additional_query_params)
        .done(function (data) {
            let template_source = $(template_id).html();
            let template = Handlebars.compile(template_source);
            data['content'].forEach(function (entity_data) {
                entity_container_element.append(template(entity_data));
            });
        });
}

/**
 * Loads a list of airports, using the selected values available in the form.
 */
function loadAirports() {
    let page = $('#airport_page').val();
    let limit = $('#airport_limit').val();
    loadCollection('/api/airports', page, limit, '#airports-list', '#airport_entity')
}

/**
 * Loads a list of carriers, using the selected values available in the form.
 */
function loadCarriers() {
    let page = $('#carrier_page').val();
    let limit = $('#carrier_limit').val();
    loadCollection('/api/carriers', page, limit, '#carriers-list', '#carrier_entity')
}

/**
 * Loads all available options for the statistics filters.
 */
function loadStatisticSelectOptions() {
    let airport_code_select = $('#statistics_airport_code');
    let carrier_code_select = $('#statistics_carrier_code');

    airport_code_select.empty();
    carrier_code_select.empty();
    airport_code_select.append($('<option value="None">None</option>'));
    carrier_code_select.append($('<option value="None">None</option>'));

    $.ajax('/api/airport_codes')
        .done(function (data) {
            data['content'].forEach(function (entity) {
                let code = entity['airport_code'];
                airport_code_select.append('<option value="' + code + '">' + code + '</option>')
            });
        });

    $.ajax('/api/carrier_codes')
        .done(function (data) {
            data['content'].forEach(function (entity) {
                let code = entity['carrier_code'];
                carrier_code_select.append('<option value="' + code + '">' + code + '</option>')
            });
        });
}

function loadStatistics() {
    let page = $('#statistics_page').val();
    let limit = $('#statistics_limit').val();
    let airport_code = $('#statistics_airport_code').val();
    let carrier_code = $('#statistics_carrier_code').val();
    let year = $('#statistics_year').val();
    let month = $('#statistics_month').val();

    let uri_params = '?page=' + page + '&limit=' + limit;

    // Check if the airport and carrier codes have been specified.
    if (airport_code !== 'None') {
        uri_params += '&airport_code=' + airport_code;
    }
    if (carrier_code !== 'None') {
        uri_params += '&carrier_code=' + carrier_code;
    }

    // Check if year is enabled, and only add it to the query if so.
    if ($('#statistics_year_enabled').is(':checked')) {
        uri_params += '&year=' + year;
    }
    if ($('#statistics_month_enabled').is(':checked')) {
        uri_params += '&month=' + month;
    }

    // Prepare the container for the data.
    let entity_container_element = $('#statistics_list');
    entity_container_element.empty();

    $.when(
        $.ajax('/api/statistics/flights' + uri_params),
        $.ajax('/api/statistics/delays' + uri_params),
        $.ajax('/api/statistics/minutes_delayed' + uri_params)
    )
        .done(function (flights_data, delays_data, minutes_delayed_data) {
            console.log('All sub-queries done.');
            console.log(flights_data[0]['content']);
            console.log(delays_data[0]['content']);
            console.log(minutes_delayed_data[0]['content']);
            let flights_content = flights_data[0]['content'];
            let delays_content = delays_data[0]['content'];
            let minutes_delayed_content = minutes_delayed_data[0]['content'];

            let template_source = $('#statistic_entity').html();
            let template = Handlebars.compile(template_source);
            for (let i = 0; i < flights_content.length; i++) {
                let statistic_object = {
                    airport_code: flights_content[i]['airport_code'],
                    carrier_code: flights_content[i]['carrier_code'],
                    year: flights_content[i]['year'],
                    month: flights_content[i]['month'],
                    flights: flights_content[i],
                    delays: delays_content[i],
                    minutes_delayed: minutes_delayed_content[i]
                };
                entity_container_element.append(template(statistic_object));
            }
        });
}

function toggleYearEnabled() {
    let enabled = $('#statistics_year_enabled').is(':checked');
    $('#statistics_year').prop('disabled', !enabled)
}

function toggleMonthEnabled() {
    let enabled = $('#statistics_month_enabled').is(':checked');
    $('#statistics_month').prop('disabled', !enabled);
}