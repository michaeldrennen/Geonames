@extends('geonames::layout')

@section('title', 'JQuery Element Example')

@section('styles')
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css"
          integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm"
          crossorigin="anonymous">

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css">
@endsection


@section('scripts')



    <script src="http://code.jquery.com/jquery-3.3.1.min.js"></script>

    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js"
            integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q"
            crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"
            integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl"
            crossorigin="anonymous"></script>
    <script>

        console.log("foo");
        $('#geonames-autocomplete-ajax').autocomplete({


            source: function (request, response) {
                $.ajax({
                    url: "/geonames/search-all",
                    dataType: "json",
                    data: "term=" + request.term,
                    success: function (data) {
                        response($.map(data, function (item) {
                            return {
                                label: item.asciiname,
                                value: item.geonameid,
                                asciiname: item.asciiname,
                                admin2_code: item.admin2_code,
                                admin_2_name: item.admin_2_name,
                                country_code: item.country_code,
                                admin1_code: item.admin1_code,
                                admin1_name: item.admin1_name,
                            };
                        }));
                    }
                });
            },


            minLength: 2,


            select: function (event, ui) {
                console.log("Selected: " + ui.item.value + " aka " + ui.item.id);
            }
        }).autocomplete("instance")._renderItem = function (ul, item) {
            var string = '';
            if (item.country_code == 'US') {
                string = item.asciiname + ", " + item.admin1_code + "<br /><p><small>" + item.admin_2_name + "</small><p>";
            } else {
                string = item.asciiname + ", " + item.country_code;
            }

            return $("<li>")

                .append("<div>" + string + "</div>")
                .appendTo(ul);
        };


    </script>

@endsection

@section('content')
    <div class="row">
        <div class="col">
            <h1>Autocomplete Example Using JQuery</h1>

            <form method="GET" action="#" accept-charset="UTF-8" id="geonames_form" autocomplete="off">
                <div class="form-group">
                    <label for="geonames-autocomplete-ajax">Start typing the name of a place</label>

                    <input placeholder="City, hospital, ski resort, etc..."
                           id="geonames-autocomplete-ajax"
                           class="form-control"
                           name="place"
                           type="text"
                           aria-describedby="geonamesAutocompleteHelp">
                    <small id="geonamesAutocompleteHelp" class="form-text text-muted">The results you get in this
                        example depends on how much of the Geonames database you installed.
                    </small>
                    <input id="geonameid"
                           name="geonameid"
                           type="hidden"
                           value="">
                    {{--<input class="alert" id="geonames-search-button" type="submit" value="Search">--}}
                </div>
            </form>

        </div>
    </div>
@endsection

