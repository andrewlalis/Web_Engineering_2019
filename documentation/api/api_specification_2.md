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
Returns a list of statistics entries in the system. In our system, a statistics entry is simply a unique combination of the four filter parameters shown above.

#### GET
Example request:
`GET http://DOMAIN/api/statistics?airport_code=ATL&carrier_code=DL&year=2015&month=3`

Example response:
```
{
  "content": [
    {
      "airport_code": "ATL",
      "carrier_code": "DL",
      "year": 2015,
      "month": 3,
      "links": {
        "self": "http://localhost:8000/api/statistics?airport_code=ATL&carrier_code=DL&year=2015&month=3",
        "airport": "http://localhost:8000/api/airports/ATL",
        "carrier": "http://localhost:8000/api/carriers/DL"
      }
    }
  ],
  "links": {
    "self": "http://localhost:8000/api/statistics?airport_code=ATL&carrier_code=DL&year=2015&month=3&page=1&limit=10",
    "first_page": "http://localhost:8000/api/statistics?airport_code=ATL&carrier_code=DL&year=2015&month=3&page=1&limit=10",
    "last_page": "http://localhost:8000/api/statistics?airport_code=ATL&carrier_code=DL&year=2015&month=3&page=1&limit=10"
  },
  "response_time": 0.014006853103638
}
```

#### POST
When posting to this resource, there are some things to keep in mind. You **must** provide all four of the aforementioned parameters, and additionally, if the `airport_code` does not exist yet (not visible in the `/airports` endpoint), you **must** provide an `airport_name` so that the new airport may be registered. The same logic applies to `carrier_code`; for any new code, a `carrier_name` must be supplied. Failure to do any of these things will return a 400 error.

Furthermore, if the statistic entry identified by the four posted parameters already exists, the code 409 will be returned.

Example request:
`POST http://DOMAIN/api/statistics Content-Type: application/x-www-form-urlencoded -data airport_code=TPA&carrier_code=DL&year=2019&month=3`

Example response:
```
{
  "content": {
    "message": "Resource created.",
    "id": 54014
  },
  "links": {
    "self": "http://localhost:8000/api/statistics"
  },
  "response_time": 0.30321097373962
}
```

#### PATCH
It is not possible to patch a statistical record, and attempting to do so will return the following result:
```
{
  "content": {
    "error_message": "You may not patch a statistical record, only a subset of the record.",
    "context": []
  },
  "links": {
    "0": "http://localhost:8000/api/statistics/flights",
    "1": "http://localhost:8000/api/statistics/delays",
    "2": "http://localhost:8000/api/statistics/minutes_delayed",
    "self": "http://localhost:8000/api/statistics"
  },
  "response_time": 0.21814179420471
}
```

Nevertheless, it is important to discuss this request type as it is handled for the three child requests in identical fashion. Each of the three child endpoints, `flights`, `delays`, and `minutes_delayed`, use the same syntax. The following rules apply when PATCHing these resources.

* All of the four identifying parameters **must** be present.
* Any of the statistical values shown in the sample GET responses may be patched by sending `that_variable_name=new_value`.

A sample request for the `flights` child endpoint is shown below as an example which will apply to all three child endpoints.

Example request:
`PATCH http://DOMAIN/api/statistics/flights Content-Type: application/x-www-form-urlencoded -data airport_code=TPA&carrier_code=DL&year=2012&month=12&total=0`
_Notice that the purpose of this request is to update the `total` variable to `0`._

Example response:
```
{
  "content": {
    "message": "Statistics patched successfully."
  },
  "links": {
    "self": "http://localhost:8000/api/statistics/flights"
  },
  "response_time": 0.27288389205933
}
```

#### DELETE
Delete requests are relatively simple. All this endpoint, as well as all three child endpoints implement them. To delete a resource, simply send the request and include all four of the identifying parameters. _Note that when the main `/statistics` entry is deleted, all child resources (`flights`, `delays`, `minutes_delayed`) are also deleted._

Example request:
`DELETE http://DOMAIN/api/statistics/flights Content-Type: application/x-www-form-urlencoded -data airport_code=TPA&carrier_code=DL&year=2012&month=12`

For delete requests, there is no data response if successful, only a `204 No Content` response code. If an error does occur, that information will be clearly provided.

### `/statistics/flights` _Paginated_
Returns a list of statistics about categorized numbers of flights.

#### GET
Example request:
`GET http://DOMAIN/api/statistics/flights?airport_code=TPA&carrier_code=DL&year=2012&month=12`

Example response:
```
{
  "content": [{
    "airport_code": "TPA",
    "carrier_code": "DL",
    "year": 2012,
    "month": 12,
    "cancelled": 1,
    "on_time": 749,
    "delayed": 136,
    "diverted": 4,
    "total": 890,
    "links": {
      "self": "http://localhost:8000/api/statistics/flights?airport_code=TPA&carrier_code=DL&year=2012&month=12",
      "airport": "http://localhost:8000/api/airports/TPA",
      "carrier": "http://localhost:8000/api/carriers/DL"
    }
  }],
  "links": {
    "self": "http://localhost:8000/api/statistics/flights?airport_code=TPA&carrier_code=DL&year=2012&month=12&page=1&limit=10",
    "first_page": "http://localhost:8000/api/statistics/flights?airport_code=TPA&carrier_code=DL&year=2012&month=12&page=1&limit=10",
    "last_page": "http://localhost:8000/api/statistics/flights?airport_code=TPA&carrier_code=DL&year=2012&month=12&page=1&limit=10"
  },
  "response_time": 0.23796105384827
}
```

_POST, PATCH, and DELETE requests are covered in the `/statistics` endpoint._

### `/statistics/delays` _Paginated_
Returns a list of statistics about categorized numbers of delays.

#### GET
Example request:
`GET http://DOMAIN/api/statistics/delays?airport_code=TPA&carrier_code=DL&year=2012&month=12`

Example response:
```
{
  "content": [{
    "airport_code": "TPA",
    "carrier_code": "DL",
    "year": 2012,
    "month": 12,
    "late_aircraft": 34,
    "weather": 3,
    "security": 0,
    "national_aviation_system": 50,
    "carrier": 48,
    "links": {
      "self": "http://localhost:8000/api/statistics/delays?airport_code=TPA&carrier_code=DL&year=2012&month=12",
      "airport": "http://localhost:8000/api/airports/TPA",
      "carrier": "http://localhost:8000/api/carriers/DL"
    }
  }],
  "links": {
    "self": "http://localhost:8000/api/statistics/delays?airport_code=TPA&carrier_code=DL&year=2012&month=12&page=1&limit=10",
    "first_page": "http://localhost:8000/api/statistics/delays?airport_code=TPA&carrier_code=DL&year=2012&month=12&page=1&limit=10",
    "last_page": "http://localhost:8000/api/statistics/delays?airport_code=TPA&carrier_code=DL&year=2012&month=12&page=1&limit=10"
  },
  "response_time": 0.24684500694275
}
```

_POST, PATCH, and DELETE requests are covered in the `/statistics` endpoint._

### `/statistics/minutes_delayed` _Paginated_
Returns a list of statistics about the number of minutes of delay.

#### GET
Example request:
`GET http://DOMAIN/api/statistics/minutes_delayed?airport_code=TPA&carrier_code=DL&year=2012&month=12`

Example response:
```
{
  "content": [{
    "airport_code": "TPA",
    "carrier_code": "DL",
    "year": 2012,
    "month": 12,
    "late_aircraft": 1727,
    "weather": 483,
    "carrier": 3079,
    "security": 3079,
    "total": 6980,
    "national_aviation_system": 1691,
    "links": {
      "self": "http://localhost:8000/api/statistics/minutes_delayed?airport_code=TPA&carrier_code=DL&year=2012&month=12",
      "airport": "http://localhost:8000/api/airports/TPA",
      "carrier": "http://localhost:8000/api/carriers/DL"
    }
  }],
  "links": {
    "self": "http://localhost:8000/api/statistics/minutes_delayed?airport_code=TPA&carrier_code=DL&year=2012&month=12&page=1&limit=10",
    "first_page": "http://localhost:8000/api/statistics/minutes_delayed?airport_code=TPA&carrier_code=DL&year=2012&month=12&page=1&limit=10",
    "last_page": "http://localhost:8000/api/statistics/minutes_delayed?airport_code=TPA&carrier_code=DL&year=2012&month=12&page=1&limit=10"
  },
  "response_time": 0.24147820472717
}
```

_POST, PATCH, and DELETE requests are covered in the `/statistics` endpoint._


## Miscellaneous
This grouping contains any endpoints which do not fit neatly into any of the three previous groups.

### `/aggregate_carrier_statistics/{airport_1_code}/{airport_2_code}`
Returns aggregate data for all statistical entries shared by the two specified airports, and can optionally be filtered by a `carrier_code`.

Example request:
`GET http://DOMAIN/api/aggregate_carrier_statistics/TPA/ATL?carrier_code=DL`

Example response:
```
{
  "content": {
    "carrier": {
      "average": 318.85855263158,
      "standard_deviation": 292.87110061728,
      "median": 181.5
    },
    "late_aircraft": {
      "average": 409.83881578947,
      "standard_deviation": 410.34275740455,
      "median": 199.5
    }
  },
  "links": {
    "self": "http://localhost:8000/api/aggregate_carrier_statistics/TPA/ATL?carrier_code=DL"
  },
  "response_time": 0.18251299858093
}
```

### `/users` _Paginated_
Returns a list of all users who have requested information from the API, with some basic aggregate statistics per user to give a quick snapshot of the user's actions.

Example request:
`GET http://DOMAIN/api/users`

Example response:
```
{
  "content": [
    {
      "id": 1,
      "address": "127.0.0.1",
      "request_count": 86,
      "most_requested_endpoint": "/users",
      "links": {
        "self": "http://localhost:8000/api/users/1"
      }
    }
  ],
  "links": {
    "self": "http://localhost:8000/api/users?page=1&limit=10",
    "first_page": "http://localhost:8000/api/users?page=1&limit=10",
    "last_page": "http://localhost:8000/api/users?page=1&limit=10"
  },
  "response_time": 0.14764595031738
}
```

### `/users/{id}`
Returns the information about a single user, identified by the provided `id`. This endpoint does not really add any value, but exists merely for the completeness of the API as a whole.

Example request:
`GET http://DOMAIN/api/users/1`

Example response:
```
{
  "content": {
    "id": 1,
    "address": "127.0.0.1"
  },
  "links": {
    "requests": "http://localhost:8000/api/users/1/requests",
    "self": "http://localhost:8000/api/users/1"
  },
  "response_time": 0.17720103263855
}
```

### `/users/{id}/requests` _Paginated_
Returns a list of all requests a user specified by the provided `id` parameter has ever made.

Example request:
`GET http://DOMAIN/api/users/1/requests`

Example response (_Note that the response is cut short by `...` for brevity_):
```
{
  "content": [
    {
      "id": 1,
      "user_id": 1,
      "occurred_at": "2019-03-28 18:32:54",
      "endpoint_uri": "/airport_codes",
      "request_type": 1,
      "links": {
        "self": "http://localhost:8000/api/users/1/requests?"
      }
    },
    {
      "id": 2,
      "user_id": 1,
      "occurred_at": "2019-03-28 18:32:54",
      "endpoint_uri": "/carrier_codes",
      "request_type": 1,
      "links": {
        "self": "http://localhost:8000/api/users/1/requests?"
      }
    },
    ...
  ],
  "links": {
    "self": "http://localhost:8000/api/users/1/requests?page=1&limit=10",
    "first_page": "http://localhost:8000/api/users/1/requests?page=1&limit=10",
    "last_page": "http://localhost:8000/api/users/1/requests?page=9&limit=10",
    "next_page": "http://localhost:8000/api/users/1/requests?page=2&limit=10"
  },
  "response_time": 0.13511109352112
}
```