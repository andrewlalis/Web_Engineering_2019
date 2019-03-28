# API Specification Version 2
In this new and improved version, a more concise, understandable overview of the various API endpoints offered by the AndyFramework system.

All endpoints can be reached via the `/api` sub-domain, and as such, all URIs in the API follow the format: `http://DOMAIN/api/...`. For more information about the specifics of the API's syntax and organization, please see **Formatting and Response Syntax**.

These endpoints can be grouped based on the type of data that they deal with. In this document you will find several groups, each of which contains one or more endpoints.

## Formatting and Response Syntax
As mentioned briefly in the introduction, all calls to the api should begin with the `/api` sub-domain. Additionally, when describing the URI for a particular endpoint, path parameters may be necessary. These are denoted with a concise variable name between two braces. For example, `/airports/{code}` indicates that the endpoint requires an airport's code to satisfy requests. If query parameters are available, these will be listed on a per-endpoint basis.

Because all endpoints are prefixed by the same `/api` prefix, this is omitted when naming the endpoints further in this document.

Unless otherwise specified, an endpoint responds only to GET requests.

### Response Syntax
All endpoints in the AndyFramework return responses of similar structure. The following principles can be expected to be followed by all endpoints.

* The response, if in JSON format, consists of three indices, `content`, `response_time`, and `links`. If in CSV format, `links` and `response_time` are omitted.

* `content` contains the actual _content_ of the endpoint, that is, this is the part of the response that contains the meaningful data which was requested.

* `links` contains zero or more HATEOAS links to the collection itself, and other related resources. Note that links may also be found within the `content`.

* `response_time` contains the response time of this endpoint, in seconds. This response time is defined as the time required for the routing system to search for the endpoint which matches the client's request and obtain a response from that endpoint.

#### Error Responses
If something goes wrong, whether it be by fault of the client or the server, an error message will be returned. This consists of the standard format discussed above, with some additional constraints on the content of the response.

All error responses must contain an `error_message` which describes the error which occurred. Optionally, the error response may also provide some data in the `context` object which, as the name implies, provides context for the error which occurred.

As a special case for the 404 resource not found error that is triggered when an unknown endpoint is called, the `links` object will contain an object named `available_resources`, which lists all endpoints in the API, just in case it is needed.

### Paginated Endpoints
Because it is often the case that an endpoint represents a collection of objects, defining some general rules for how all such endpoints behave will simplify the rest of the specification greatly.

All endpoints which are paginated will be indicated clearly as such. Paginated endpoints follow the following rules:

* Two optional query parameters are **always** permitted: `page` and `limit`. Both must be positive integer values. The API takes preventative measures in setting a global upper-limit of 50 resources per page. Any `limit` greater than this will be ignored.

* The `links` object will contain links to other pages of this resource which may be useful. These may be:
	* `first_page`
	* `last_page`
	* `next_page` _Only shown if a next page exists._
	* `previous_page` _Only shown if a previous page exists._

* Requesting a page which is beyond the range from `first_page` to `last_page` will result in an empty `content`, but it is acceptable to make such a request.

# Endpoint Groups

## Airports
This group contains endpoints related exclusively to the list of airports which is persisted by the system.

### `/airports` _Paginated_
Returns a list of airports that exist in the system.

Example request:
`GET http://DOMAIN/api/airports?page=1&limit=5`

Example response (_Note that the response is cut short by `...` for brevity_):
```
{
  "content": [
    {
      "airport_code": "ATL",
      "airport_name": "Atlanta, GA: Hartsfield-Jackson Atlanta International",
      "links": {
        "self": "http://localhost:8000/api/airports/ATL"
      }
    },
    {
      "airport_code": "BOS",
      "airport_name": "Boston, MA: Logan International",
      "links": {
        "self": "http://localhost:8000/api/airports/BOS"
      }
    },
    ...
  ],
  "links": {
    "self": "http://localhost:8000/api/airports?page=1&limit=5",
    "first_page": "http://localhost:8000/api/airports?page=1&limit=5",
    "last_page": "http://localhost:8000/api/airports?page=6&limit=5",
    "next_page": "http://localhost:8000/api/airports?page=2&limit=5"
  },
  "response_time": 0.00052309036254883
}
```

### `/airports/{code}`
Returns the information for a single airport specified by `code`, where `code` is the unique code for the airport as it appears in the response from the `/airports` endpoint.

Example request:
`GET http://DOMAIN/api/airports/ATL`

Example response:
```
{
  "content": {
    "id": 1,
    "airport_code": "ATL",
    "airport_name": "Atlanta, GA: Hartsfield-Jackson Atlanta International"
  },
  "links": {
    "self": "http://localhost:8000/api/airports/ATL"
  },
  "response_time": 0.00028896331787109
}
```

### `/airport_codes`
A unique endpoint which satisfies a particular need encountered during development of the front-end website; the need for a way to get all possible airport codes (without all the extra data).

Example request:
`GET http://DOMAIN/api/airport_codes`

Example response (_Note that the response is cut short by `...` for brevity_):
```
{
  "content": [
    {
      "airport_code": "ATL"
    },
    {
      "airport_code": "BOS"
    },
    {
      "airport_code": "BWI"
    },
    ...
  ],
  "links": {
    "self": "http://localhost:8000/api/airport_codes"
  },
  "response_time": 0.00038886070251465
}
```

## Carriers
Just as in the previous _Airports_ group, the Carriers group contains endpoints related exclusively to the list of carriers which this system maintains.

### `/carriers` _Paginated_
Returns a list of carriers that exist in the system.

Example request:
`GET http://DOMAIN/api/carriers?page=1&limit=5`

Example response (_Note that the response is cut short by `...` for brevity_):
```
{
  "content": [
    {
      "carrier_code": "AA",
      "carrier_name": "American Airlines Inc.",
      "links": {
        "self": "http://localhost:8000/api/carriers/AA"
      }
    },
    {
      "carrier_code": "AS",
      "carrier_name": "Alaska Airlines Inc.",
      "links": {
        "self": "http://localhost:8000/api/carriers/AS"
      }
    },
    ...
  ],
  "links": {
    "self": "http://localhost:8000/api/carriers?page=1&limit=5",
    "first_page": "http://localhost:8000/api/carriers?page=1&limit=5",
    "last_page": "http://localhost:8000/api/carriers?page=6&limit=5",
    "next_page": "http://localhost:8000/api/carriers?page=2&limit=5"
  },
  "response_time": 0.00048089027404785
}
```

### `/carriers/{code}`
Returns the information for a single carrier identified by `code`, where `code` is the unique sequence of characters that is found as `carrier_code` in the `/carriers` endpoint.

Example request:
`GET http://DOMAIN/api/carriers/DL`

Example response:
```
{
  "content": {
    "id": 6,
    "carrier_code": "DL",
    "carrier_name": "Delta Air Lines Inc."
  },
  "links": {
    "self": "http://localhost:8000/api/carriers/DL"
  },
  "response_time": 0.00027704238891602
}
```

### `/carrier_codes`
Just like `/airport_codes` this endpoint provides an additional convenience method for obtaining the list of all carriers available in the system at once.

Example request:
`GET http://DOMAIN/api/carrier_codes`

Example response (_Note that the response is cut short by `...` for brevity_):
```
{
  "content": [
    {
      "carrier_code": "9E"
    },
    {
      "carrier_code": "AA"
    },
    ...
  ],
  "links": {
    "self": "http://localhost:8000/api/carrier_codes"
  },
  "response_time": 0.00027704238891602
}
```

## Statistics
Since original statistics data provided by the dataset could be broken down into several smaller, more manageable pieces, each of the endpoints required to serve all statistics is shown here.

All of the endpoints in this group are unique in that not only do they accept the normal pagination query parameters of `page` and `limit`, but also four other parameters used to filter the (initially very large) dataset. These are:

* `airport_code` The code for an airport to filter by.
* `carrier_code` The code for a carrier to filter by.
* `year` An integer year. Only show results from this year, if this is provided.
* `month` An integer month from 1 to 12. Only show results from this month, if this is provided.

All of the endpoints in this group also support the following additional HTTP methods: POST, PATCH, and DELETE. When performing a request with one of these verbs, the four query parameters mentioned above **must** be included in the payload of the request, or it will be rejected.



### `/statistics` _Paginated_
Returns a list of statistics entries in the system.

### `/statistics/flights` _Paginated_

### `/statistics/delays` _Paginated_

### `/statistics/minutes_delayed` _Paginated_

## Miscellaneous
This grouping contains any endpoints which do not fit neatly into any of the three previous groups.

### `/aggregate_carrier_statistics/{airport_1_code}/{airport_2_code}`