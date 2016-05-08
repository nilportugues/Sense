<?php
/**
 * Created by Asier Marqués <asiermarques@gmail.com>
 * Date: 8/5/16
 * Time: 2:00
 */

namespace Simettric\Sense\Router;


use Simettric\Sense\ActionResult\ActionResultInterface;
use Simettric\Sense\ActionResult\WPTemplateActionResult;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class Router {



    public $controller_instance, $action_name;

    /**
     * @var RouteContainer
     */
    private $routeContainer;

    /**
     * @var Container
     */
    private $_container;

    private $_already_matched = false;

    function __construct(RouteContainer $routeContainer, Container $container){
        $this->_container = $container;
        $this->routeContainer = $routeContainer;
    }





    function init(){



        $extra = array();
        foreach ($this->routeContainer->getRoutes() as $name=>$route) {


            \add_rewrite_rule($route["regexp"], $route["url"],'top');

            $params = array_merge($route["params"], array_keys($route["url_params"]));

            foreach($params as $param){
                $extra[$param] = $param;
            }
        }


        \add_filter("query_vars", function($qvars) use ($extra){
            $qvars[] = '__controller';
            $qvars[] = '__action';
            $qvars[] = "__route_name";
            foreach ($extra as $param) {
                $qvars[] = $param;
            }

            return $qvars;
        });

        \add_action("parse_query", array($this, "match"));

    }

    function match(){


        global $wp_query;

        \remove_action("parse_query", array($this, "match"));

        if(!$wp_query->is_main_query() || $this->_already_matched) return;


        if(isset($wp_query->query_vars["__controller"]) &&
            $controller_classname = $wp_query->query_vars["__controller"]){

            $this->_already_matched = true;



            $action_name = $wp_query->query_vars["__action"] ? $wp_query->query_vars["__action"] . "Action" : "indexAction";



            $controller_classname = str_replace('\\\\','\\', $controller_classname);

            $this->controller_instance  = new $controller_classname($this->_container);

            $actionResult = $this->controller_instance->$action_name(
                $this->_container->get("request"),
                $this->_container->get("wp.query")
            );



            if($actionResult instanceof ActionResultInterface){

                $actionResult->execute();

            }





        }

    }

} 