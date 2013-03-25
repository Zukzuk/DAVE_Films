/*
 @Author: Dave Timmerman
 */

Model = function(data) {
    this.init(data);
};

$.extend(Model.prototype, {
    // startup variables
    base_url : '', // set in application/config/config.php
    environment : '', // set in index.php
    version : '', // set in index.php
    app_name : '', // set in application/controllers/init.php, added to DOM in application/views/template/footer.php
    google_track_id : '', // set in application/controllers/init.php, added to DOM in application/views/template/footer.php
    language : '', // set in application/controllers/init.php, added to DOM in application/views/template/footer.php
    js_deeplink : '', // set in application/controllers/init.php, added to DOM in application/views/template/footer.php
    responsive : '', // set in application/controllers/init.php, added to DOM in application/views/template/footer.php
    login_target : '', // set in application/controllers/init.php, added to DOM in application/views/template/footer.php
    login_at_startup : '', // set in application/controllers/init.php, added to DOM in application/views/template/footer.php
    developer : '', // set as GETTER in url (for example : base_url/?developer=dt)

    // app variables
    developer_lib : ["app", "fb", "dt"],
    xhr_pool : [],
    window_size : [],
    device : {
        type : "unknown",
        mobile_small : 320,
        mobile : 480,
        tablet_small : 640,
        tablet : 960,
        desktop : 1300,
        wide : 1920,
        full : 2560
    },
    routing : {
        type : "",
        view : "",
        filters : { }
    },
    external_uri_change : function(data) { },
    current_page : '',

    filter_types : ['play', 'id'],
    pages : ["login", "test"],
    securedpages : ["films"],
    landing_page : 'login',
    landing_securedpage : 'films',

    // app flags
    startup_complete : false,

    // custom
    firstname : '',
    lastname : '',
    role_id : '',

    window_width : 0,
    window_height : 0,
    scroll_top : 0,
    scroll_interval : {},

    current_offset : 0,
    num_of_pages : 0,
    films_per_row : 0,
    films_per_page : 60,

    search_offset : 0,
    search_id : 0,

    server_base : 'http://localhost/films/',
    file_base : 'file:///F:/',
    films_json : { },
    alphabet_count : 0, //'#'
    film_id : 0,
    film_name : '',
    film_file : '',
    film_poster : '',
    film_uri : '',
    poster_uri : '',

    ///////////////
    // initialize
    ///////////////

    init : function(data) {
        // do initialization here
        this.base_url = data.base_url;
        this.environment = data.environment;
        this.version = data.version;
        this.app_name = data.app_name;
        this.google_track_id = data.google_track_id;
        this.language = data.language;
        this.js_deeplink = data.js_deeplink;
        this.responsive = data.responsive;
        this.login_target = data.login_target;
        this.login_at_startup = data.login_at_startup;
        this.developer = data.developer;

        console.log(this.app_name + "\n", "version : " + this.version + "\n", "base_url : " + this.base_url + "\n", "environment : " + this.environment + "\n", "language : " + this.language + "\n", "tracking : " + this.google_track_id + "\n", "js_deeplink : " + this.js_deeplink + "\n", "responsive : " + this.responsive + "\n", "login_target : " + this.login_target + "\n", "login_at_startup : " + this.login_at_startup + "\n", "responsive : " + this.responsive);

        this.setup_console();
        this.setup_window();
    },

    clear : function() {
        // clear class here
        this.setup_console = function() { };
        this.setup_routing = function() { };
        this.toggle_routing_filter = function() { };
        this.replace_routing_filter = function() { };
        this.get_routing_uri = function() { };
        this.setup_device = function() { };
        this.setup_window = function() { };
    },

    setup : function() {
        // do setup here
    },

    ///////////////
    // Methods
    ///////////////

    setup_console : function() {
        // set logging according to environment
        if (this.developer == '')
            this.developer = this.developer_lib.toString();
        // get developer(s)
        var explode = this.developer.split(',');
        for (var i = 0; i < explode.length; i++) {
            var developer = explode[i];
            // add specific developer logging
            for (var j = 0; j < this.developer_lib.length; j++) {
                if (this.developer_lib[j] == developer) {
                    console[this.developer_lib[j]] = console.log;
                    // remove selected log for production
                    if (this.environment == 'production')
                        console[this.developer_lib[j]] = function() {
                        };
                    this.developer_lib = $.grep(this.developer_lib, function(value) {
                        return value != developer;
                    });
                }
            }
        }
        for (var i = 0; i < this.developer_lib.length; i++) {
            // nullify other logs
            console[this.developer_lib[i]] = function() {
            };
        }
        // nullify all remaining logs on production
        if (this.environment == 'production') {
            if (!window.console)
                window.console = { };
            var methods = ["log", "debug", "warn", "info"];
            for (var i = 0; i < methods.length; i++) {
                console[methods[i]] = function() {
                };
            }
        }
    },

    setup_routing : function(deeplink) {
        var type = '', view = '', active_filters = { }, i = 0;
        if (deeplink.path != '/') {
            if (view == '') {
                for ( i = 0; i < this.pages.length; i++) {
                    if (this.pages[i] == deeplink.pathNames[0]) {
                        // setup pages without login check
                        type = 'page';
                        view = deeplink.pathNames[0];
                        break;
                    }
                }
            }
            if (view == '') {
                for ( i = 0; i < this.securedpages.length; i++) {
                    if (this.securedpages[i] == deeplink.pathNames[0]) {
                        // setup pages with login check
                        type = 'securedpage';
                        view = deeplink.pathNames[0];
                        break;
                    }
                }
            }
            if (view == '') {
                // setup error pages
                type = 'errorpage';
                view = '404';
                if (app.model.environment != 'production')
                    alert("'setup_routing' :: '" + view + "' does not exist in app.model.pages or app.model.securedpages array. Please add!");
            }
        }

        // add further filters per page
        for ( i = 0; i < this.filter_types.length; i++) {
            // get filter_type from model
            var filter_type = this.filter_types[i];
            for ( j = 1; j < deeplink.pathNames.length; j++) {
                // check if current deeplink.pathName is the same as selected filter_type
                if (deeplink.pathNames[j] == filter_type) {
                    // if filter_type is present in the uri, add it as an Array to the routing Object
                    active_filters[filter_type] = [];
                    console.log("adding filter_type :: " + filter_type);
                }
                else if ( typeof active_filters[filter_type] != 'undefined') {
                    // create filter_checklist
                    var checklist = jQuery.grep(this.filter_types, function(value) {
                        return value != filter_type;
                    });
                    if ($.inArray(deeplink.pathNames[j], checklist) == -1) {
                        // if the deeplink.pathName is not a filter_type, add as value
                        active_filters[filter_type].push(deeplink.pathNames[j]);
                        console.log("adding value to " + filter_type + " :: " + deeplink.pathNames[j]);
                    }
                    else {
                        // deeplink.pathName is a filter_type, stop loop
                        break;
                    }
                }
            }
        }

        this.routing = {
            type : type,
            view : view,
            filters : active_filters
        };
        console.log('setup_routing :: ', this.routing);
    },

    toggle_routing_filter : function(filter_type, filter_value) {
        if (this.check_filter_types(filter_type)) {
            if ( typeof this.routing.filters[filter_type] == 'undefined') {
                // filter type does not exist, add and fill
                this.routing.filters[filter_type] = [];
                this.routing.filters[filter_type].push(filter_value);
            }
            else {
                // filter_type exists
                for ( i = 0; i < this.routing.filters[filter_type].length; i++) {
                    var existing_value = this.routing.filters[filter_type][i];
                    // check if value should be added or removed
                    if (filter_value == existing_value) {
                        // remove value when toggled
                        this.routing.filters[filter_type] = jQuery.grep(this.routing.filters[filter_type], function(value) {
                            return value != filter_value;
                        });
                        break;
                    }
                    if (i == this.routing.filters[filter_type].length - 1) {
                        // add value if new
                        this.routing.filters[filter_type].push(filter_value);
                        break;
                    }
                }
            }
        }

        console.log('toggle_routing_filter :: ', this.routing);
    },

    replace_routing_filter : function(filter_type, filter_value) {
        if (this.check_filter_types(filter_type)) {
            if ( typeof this.routing.filters[filter_type] == 'undefined') {
                // filter type does not exist, add and fill
                this.routing.filters[filter_type] = [];
                this.routing.filters[filter_type].push(filter_value);
            }
            else {
                // filter_type exists
                this.routing.filters[filter_type][0] = filter_value;
            }
        }

        console.log('replace_routing_filter :: ', this.routing);
    },

    get_routing_uri : function() {
        var uri = this.routing.view + '/';

        for ( i = 0; i < this.filter_types.length; i++) {
            var filter_type = this.filter_types[i];
            if ( typeof this.routing.filters[filter_type] != 'undefined') {
                for ( j = 0; j < this.routing.filters[filter_type].length; j++) {
                    if (j == 0) {
                        uri += filter_type + '/';
                    }
                    uri += this.routing.filters[filter_type][j] + '/';
                }
            }
        }

        console.log('get_routing_uri :: uri', uri);
        return uri;
    },

    check_routing_filter : function(filter) {
        if ( typeof this.routing.filters[filter] == 'undefined')
            return false;
        else
            return true;
    },

    check_filter_types : function(filter) {
        for (var i in this.filter_types) {
            if (this.filter_types[i] == filter) {
                return true;
            }
        }
        return false;
    },

    setup_device : function(device_change) {
        this.window_size = [$(window).width() - 100, $(window).height() - 100];
        if ( typeof device_change == 'undefined')
            device_change = false;

        if (this.window_size[0] <= this.device.mobile_small && this.device.type != 'mobile_small') {
            $('body').removeClass(this.device.type).addClass('mobile_small');
            this.device.type = 'mobile_small';
            this.films_per_row = 1;
            device_change = true;
        }
        else if (this.window_size[0] > this.device.mobile_small && this.window_size[0] <= this.device.mobile && this.device.type != 'mobile') {
            $('body').removeClass(this.device.type).addClass('mobile');
            this.device.type = 'mobile';
            this.films_per_row = 2;
            device_change = true;
        }
        else if (this.window_size[0] > this.device.mobile && this.window_size[0] <= this.device.tablet_small && this.device.type != 'tablet_small') {
            $('body').removeClass(this.device.type).addClass('tablet_small');
            this.device.type = 'tablet_small';
            this.films_per_row = 3;
            device_change = true;
        }
        else if (this.window_size[0] > this.device.tablet_small && this.window_size[0] <= this.device.tablet && this.device.type != 'tablet') {
            $('body').removeClass(this.device.type).addClass('tablet');
            this.device.type = 'tablet';
            this.films_per_row = 4;
            device_change = true;
        }
        else if (this.window_size[0] > this.device.tablet && this.window_size[0] <= this.device.desktop && this.device.type != 'desktop') {
            $('body').removeClass(this.device.type).addClass('desktop');
            this.device.type = 'desktop';
            this.films_per_row = 6;
            device_change = true;
        }
        else if (this.window_size[0] > this.device.desktop && this.window_size[0] <= this.device.wide && this.device.type != 'wide') {
            $('body').removeClass(this.device.type).addClass('wide');
            this.device.type = 'wide';
            this.films_per_row = 8;
            device_change = true;
        }
        else if (this.window_size[0] > this.device.wide && this.device.type != 'full') {
            $('body').removeClass(this.device.type).addClass('full');
            this.device.type = 'full';
            this.films_per_row = 10;
            device_change = true;
        }

        if (device_change) {
            var film_list = $('#films li');
            film_list.removeClass('left');
            for (var i = 0; i < film_list.length; i++) {
                if (!(i % this.films_per_row))
                    $(film_list[i]).addClass('left');
            };
            console.log("device.type : " + this.device.type + " | size : " + this.window_size[0] + "/" + this.window_size[1]);
            return true;
        }
        return false;
    },

    setup_window : function() {
        //this.window_width = $(window).width();
        //this.window_height = $(window).height();
        this.scroll_top = $('.wrapper').scrollTop();
        //console.log('window_width : ' + this.window_width);
        //console.log('window_height : ' + this.window_height);
        //console.log('scroll_top : ' + this.scroll_top);
    }
});
