require('./jquery-ui-mapwidget.js');
import MouseWheelZoom from 'ol/interaction/MouseWheelZoom';
import { buffer } from 'ol/extent'
import { transformExtent } from 'ol/proj'
import Point from 'ol/geom/Point';


$.widget( "custom.samplewidget", $.custom.mapwidget, {
    options: {
        color: 'rgba(255,255,255,1)'
    },

    /**
     * Creation du widget
     */
    _create: function() {
        let self = this;

        // Appel de la fonction _create du parent
        this._super();  

        // Sur mousewheel, l'emplacement de la souris comme point d'ancrage est desactive
        // le centre est utilise
        this._map.getInteractions().forEach((interaction) => {
            if (interaction instanceof MouseWheelZoom) {
                interaction.setMouseAnchor(false);
            }
        });
          
        this._drawExtentBind = this._drawExtent.bind(this);
        this._onMoveEndBind = this._onMoveEnd.bind(this);

        // La couche est chargee
        this.element.bind('samplewidgetinitialized', function() {
            // calcul de l'emprise pour 10 tuiles a ce niveau de zoom (this.options.zoom)
            self._initialize();

            self._map.on("moveend", self._onMoveEndBind);
            self._map.on('postcompose', self._drawExtentBind);  
        });
    },

    /**
     * Suppression du widget
     */
    _destroy: function () {
        this._super();
    },

    /**
     * Mise en place du zoom pour s'ajuster à l'emprise de 10 tuiles
     */
    _initialize: function() {
        let numPixelsX = this.element[0].clientWidth;
        let numPixelsY = this.element[0].clientHeight;
        
        this._size = this._map.getView().getResolutionForZoom(this.options.zoom) * 256 * 10;
        
        let resX = this._size / numPixelsX;
        let resY = this._size / numPixelsY;
        let resMax = Math.max(resX, resY);

        this.options.zoom = Math.floor(this._map.getView().getZoomForResolution(resMax))        
        this._map.getView().setZoom(this.options.zoom);
        this._map.getView().setMaxZoom(this.options.zoom);
    },

    /**
     * Dessin du masque et de l'echantillon
     * @param {*} event 
     */
    _drawExtent: function(event) {
        let dxy = this._size / this._map.getView().getResolutionForZoom(this.options.zoom);
    
        let dxy2 = dxy / 2;
    
        let ctx = event.context;
        let ratio = event.frameState.pixelRatio;
    
        ctx.save();
        ctx.scale(ratio, ratio);
        let cx = ctx.canvas.width / (2 * ratio);
        let cy = ctx.canvas.height / (2 * ratio);
    
        ctx.beginPath();
        
        // La partie noire opacité 0.3 (surface principale clockwise)
        ctx.moveTo(0, 0);
        ctx.lineTo(ctx.canvas.width, 0);
        ctx.lineTo(ctx.canvas.width, 0);
        ctx.lineTo(ctx.canvas.width, ctx.canvas.height);
        ctx.lineTo(0, ctx.canvas.height);

        // l'echantillon (trou anti-clockwise)
        ctx.moveTo(cx + dxy2, cy - dxy2);
        ctx.lineTo(cx - dxy2, cy - dxy2);
        ctx.lineTo(cx - dxy2, cy + dxy2);
        ctx.lineTo(cx + dxy2, cy + dxy2);
        ctx.closePath();

        ctx.fillStyle = 'rgba(0, 0, 0, 0.3)';
        ctx.fill();

        ctx.lineWidth = 3;
        ctx.strokeStyle = this.options.color;
        ctx.strokeRect(cx - dxy2, cy - dxy2, dxy, dxy);

        ctx.restore();
    },

    /**
     * Evenement provenant d'un zoom ou du deplacement sur la carte
     */
    _onMoveEnd: function() {
        let newZoom = this._map.getView().getZoom();
        if (this.options.zoom != newZoom) {
            this.options.zoom = newZoom;
            this._map.render();
        }  
    },

    /**
     * Redefini le bottomLevel (zoom max)
     * @param {integer} bottomLevel 
     */
    setBottomLevel: function(bottomLevel) {
        // On remet le maxZoom a son etat original sinon this._map.getView().getZoomForResolution (ligne 54)
        // retourne le nouveau zoom max
        this._map.getView().setMaxZoom(this.options.maxZoom);
        
        this.options.zoom = bottomLevel;
        this._initialize();
    },

    /**
     * Retourne l'extent de l'echantillon en lon,lat
     * @returns 
     */
    getExtent: function() {
        let point = new Point(this._map.getView().getCenter());

        let extent = point.getExtent();
        extent = buffer(extent, this._size / 2);
        return transformExtent(extent, 'EPSG:3857', 'EPSG:4326');
    }
});