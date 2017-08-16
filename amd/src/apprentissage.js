/*
 * @package    mod_cma
 * @copyright  2017 Alain Bolli
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module mod_cma/apprentissage
  */
  define(['jquery'], function($) {

      var point = ''; // Nombre de points à affecter.
      var serie = 1; // Série de mot en cours (1 à 9).

      // Search for the next page to display.
      var getDestination = function (string) {
        var dest = string.split('#');
        return "#"+dest[1];
    };

    // Search if user has not affected the same point to different words.
    var getDoublon = function (serie) {
        var doublon = 0; // Nombre de points sur une page.
        var cl = "#cmapage"+serie+" span[data-role='points']";
        var points = new Array();
        $(cl).each(function() { // Create an array with the points affected
                    var string = $(this).html();
                    var int = parseInt(string);
                    points.push(int);
        });
        var i;
        var j;
        for (j = 0; j < 4; ++j) {
            for (i = j + 1; i < 4;++i) {
                  if (points[i] == points[j]) {
                    doublon = 1;
                    break;
                  }
            }
        }
        return doublon;
        };

        // Get number of words with points in one serie.
        var getWordsAssigned = function (serie) {
            var i = 0;
            var cl = "#cmapage"+serie+" span[data-role='points']";
            $(cl).each(function() {
                var string = $(this).html();
                if (string != '') {
                        i++;
                }
            });
            return i;
        };

        // Display points when clicking on a word.
        var affichePoints = function(mot,point,serie) {
            var reste='';
            if (point=="") {
                point=4;
            } else {
                point--;
            }
            if (point==0) {point=4;}
            var numero;
            numero=$(mot).attr("id").substr(6,2); // Numéro du mot.
            $("#cmapoint"+numero).html(point);// Affiche les points pour le mot.
            $("input[name='word"+numero+"']").attr('value',point);// Ajoute le point dans un champ caché d'un formulaire.
            reste=point-1;//nombre de points restant à affecter
            if (reste==0) {
                reste=4;//on remet les 4 points ce qui permet de changer d'avis
            }
            $("span[id*='affecter']").html(" "+reste+" ");//affiche les points pour le prochain mot
            if (reste==1) {$("span[id*='pluriel']").html("");} //gestion du pluriel, on enlève le s à 1 point restant
            if (reste==4) {$("span[id*='pluriel']").html("s");} // gestion du pluriel, on remet le s
            var nb = getWordsAssigned(serie);
            if (nb ==4) {
                var doublon = getDoublon(serie);
                if (doublon  == 0) {
                    $("a[data-role='continue']").show();
                } else {
                    $("a[data-role='continue']").hide();
                }
            }
      return point;
  };

  return {

      init : function() {

          // Hide all word series, but not the first and intro text.
          $(function() {
            $("div[data-role='page'], a[data-role='continue']").hide();
            $('#cmapage1, #intro').show();
        });

          // Click on a word.
          $("div.mot").click(function() {
            point=affichePoints(this,point,serie);
        });

          // Click on continue button
          $("a[data-role='continue']").click(function() {
                $("div[data-role='page']").hide();
                $("a[data-role='continue']").hide();
                var dest = getDestination(this.href);
                $(dest+', #intro').show();
                serie++; // On augmente le numero de la page.
                point='';
                $("span[id*='affecter']").html(" 4 ");
                $("span[id*='pluriel']").html("s");
        });
      }
  };
});