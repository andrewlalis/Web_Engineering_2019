/**
 * Only once the document is fully-loaded should the madness begin.
 */
$(document).ready(function () {
    loadCodeSelectors(); // Load the list of airport and carrier codes for any select elements on the page which need them.

    // Load all data with some initial default ajax requests.
    loadAirports();
    loadCarriers();
    loadStatistics();

    // Add listeners for when the user clicks to submit new ajax requests.
    $('#airport_submit').click(loadAirports);
    $('#carrier_submit').click(loadCarriers);
    $('#statistics_submit').click(loadStatistics);
    $('#aggregate_statistics_submit').click(loadAggregateStatistics);

    // Add listeners for when the user enables/disables year and month selection.
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
    loadCollection('/api/airports', page, limit, '#airports_results', '#airport_entity')
}

/**
 * Loads a list of carriers, using the selected values available in the form.
 */
function loadCarriers() {
    let page = $('#carrier_page').val();
    let limit = $('#carrier_limit').val();
    loadCollection('/api/carriers', page, limit, '#carriers_results', '#carrier_entity')
}

/**
 * Loads all available options for any available select elements on the page.
 */
function loadCodeSelectors() {
    let airport_code_selectors = $('.airport_code_selector');
    let carrier_code_selectors = $('.carrier_code_selector');

    airport_code_selectors.empty();
    carrier_code_selectors.empty();

    airport_code_selectors.each(function (index, element) {
        let $element = $(element);
        if ($element.data('optional') === true) {
            $element.append('<option value="None">None</option>');
            $element.val('None');
        }
    });

    carrier_code_selectors.each(function (index, element) {
        let $element = $(element);
        if ($element.data('optional') === true) {
            $element.append('<option value="None">None</option>');
            $element.val('None');
        }
    });

    $.when(
        $.ajax('/api/airport_codes'),

        $.ajax('/api/carrier_codes')
    )
        .done(function (airport_codes_data, carrier_codes_data) {
            let airport_codes = airport_codes_data[0]['content'];
            let carrier_codes = carrier_codes_data[0]['content'];

            airport_codes.forEach(function (entity) {
                let code = entity['airport_code'];
                airport_code_selectors.append('<option value="' + code + '">' + code + '</option>');
            });

            carrier_codes.forEach(function (entity) {
                let code = entity['carrier_code'];
                carrier_code_selectors.append('<option value="' + code + '">' + code + '</option>');
            });

            onCodeSelectorLoadingComplete();
        });
}

/**
 * What to do when all code selectors have been completely loaded. This is necessary for anything which has a mandatory
 * option, yet also must fetch some data when the page first loads.
 */
function onCodeSelectorLoadingComplete() {
    loadAggregateStatistics();
}

/**
 * Loads statistics data from the three endpoints.
 */
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
    let entity_container_element = $('#statistics_results');
    entity_container_element.empty();

    $.when(
        $.ajax('/api/statistics/flights' + uri_params),
        $.ajax('/api/statistics/delays' + uri_params),
        $.ajax('/api/statistics/minutes_delayed' + uri_params)
    )
        .done(function (flights_data, delays_data, minutes_delayed_data) {
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

/**
 * Loads aggregate statistical data.
 */
function loadAggregateStatistics() {
    let airport_code_1 = $('#aggregate_statistics_airport_code_1').val();
    let airport_code_2 = $('#aggregate_statistics_airport_code_2').val();
    let carrier_code = $('#aggregate_statistics_carrier_code').val();
    let uri = '/api/aggregate_carrier_statistics/' + airport_code_1 + '/' + airport_code_2;
    if (carrier_code !== 'None') {
        uri += '?carrier_code=' + carrier_code;
    }

    $.ajax(uri)
        .done(function (data) {
            let template_source = $('#aggregate_statistic_entity').html();
            let template = Handlebars.compile(template_source);
            let results_container = $('#aggregate_statistics_results');
            results_container.empty();
            results_container.append(template(data['content']));
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