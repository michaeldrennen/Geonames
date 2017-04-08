@extends('geonames::layout')

@section('title', 'Vue Element Example')

@section('styles')
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/css/bootstrap.min.css"
          integrity="sha384-rwoIResjU2yc3z8GV/NPeZWAv56rSmLldC3R/AZzGRnGxQQKnKkoFVhFQhNUwEyJ" crossorigin="anonymous">
    <link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-default/index.css">
@endsection


@section('scripts')
    <script src="https://unpkg.com/vue"></script>

    <script src="https://code.jquery.com/jquery-3.1.1.slim.min.js"
            integrity="sha384-A7FZj7v+d/sdmMqp/nOQwliLvUsJfDHW+k9Omg/a/EheAdgtzNs3hpfag6Ed950n"
            crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tether/1.4.0/js/tether.min.js"
            integrity="sha384-DztdAPBWPRXSA/3eYEEUWrWCy7G5KFbe8fFjk5JAIxUYHKkDx6Qin1DkWx51bBrb"
            crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-alpha.6/js/bootstrap.min.js"
            integrity="sha384-vBWWzlZJ8ea9aCX4pEW3rVHjgjt7zpkNpZk+02D9phzyeVkE+jo0ieGizqPLForn"
            crossorigin="anonymous"></script>

    <script src="https://unpkg.com/element-ui/lib/index.js"></script>

    <script>

        import Vue from 'vue'
        import Element from 'element-ui'

        Vue.use(Element);;;;;;;;;;;;;;;;;;;;;;


        new Vue({
            el: '#app',
            data: function () {
                return {visible: false}
            }
        })

    </script>

@endsection

@section('content')


    <div id="app">
        <el-button @click="visible = true">Button</el-button>
        <el-dialog v-model="visible" title="Hello world">
            <p>Try Element</p>
        </el-dialog>
    </div>

@endsection

