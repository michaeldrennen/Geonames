@extends('geonames::layout')

@section('title', 'JQuery Element Example')

@section('styles')
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css"
          integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
@endsection


@section('scripts')


    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js"
            integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb"
            crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js"
            integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn"
            crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
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
                                country_code: item.country_code,
                                admin_2_name: item.admin_2_name
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
                string = item.asciiname + ", " + item.admin2_code + "<br />" + item.admin_2_name;
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


    <form method="GET" action="#" accept-charset="UTF-8" id="geonames_form" autocomplete="off">
        <input placeholder="City" id="geonames-autocomplete-ajax" class="" name="place" type="text">
        <input id="geonameid" name="geonameid" type="hidden" value="">
        <input class="alert" id="geonames-search-button" type="submit" value="Search">
    </form>


@endsection

