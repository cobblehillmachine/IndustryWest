
var foomanGridMassaction = Class.create(varienGridMassaction, {
    apply: function($super) {

        //carrier choices
        var carrierChoices = [];
        $('sales_order_grid_table').getElementsBySelector('.fooman_carrier').each(function(s) {
            carrierChoices.push (s.readAttribute('rel')+'|'+s.value);
        });        
        new Insertion.Bottom(this.formAdditional, this.fieldTemplate.evaluate({name: 'carrier', value: carrierChoices}));

        //tracking numbers choices
        var trackingNumbers = [];
        $('sales_order_grid_table').getElementsBySelector('.fooman_tracking').each(function(s) {
            trackingNumbers.push (s.readAttribute('rel')+'|'+s.value);
        });
        new Insertion.Bottom(this.formAdditional, this.fieldTemplate.evaluate({name: 'tracking', value: trackingNumbers}));
        
        return $super();
    }
});

var foomanGrid = Class.create(varienGrid, {
    doFilter : function($super){
        var asked = false;
        $('sales_order_grid_table').getElementsBySelector('.fooman_tracking').each(function(s) {
            if(s.value && !asked) {
                asked=true;
                if(window.confirm('If you continue you will loose the already entered tracking numbers.')){ return $super();}else{return false};
            };
        });
        if(!asked){
            return $super();
        }
    }    
});
