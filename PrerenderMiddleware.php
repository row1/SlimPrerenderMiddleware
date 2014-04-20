<?php
class PrerenderMiddleware extends \Slim\Middleware
{
  protected $backendURL;
  protected $token;
  protected $render = false;

  public function __construct($backendURL, $token)
  {
      $this->backendURL = $backendURL;
      $this->token = $token;
  }
  public function call()
  {
      //The Slim application
      $app = $this->app;

      //The Environment object
      $env = $app->environment;

      //The Request object
      $req = $app->request;

      //The Response object
      $res = $app->response;

      $agent = $req->getUserAgent();
      $bots = "!(Googlebot|bingbot|Googlebot-Mobile|Yahoo|YahooSeeker|FacebookExternalHit|Twitterbot|TweetmemeBot|BingPreview|developers.google.com/\+/web/snippet/)!i";

      if (isset($_GET['_escaped_fragment_'])){
        $init = $this->backendURL . $env['slim.url_scheme'] . '://' . $env['HTTP_HOST'] . '/?_escaped_fragment_=' . $_GET['_escaped_fragment_'];
        $this->render = true;
      } else if(preg_match($bots, $agent)){
        $resourceUri = $req->getResourceUri();
        $init = $this->backendURL . $env['slim.url_scheme'] . '://' . $env['HTTP_HOST'] . $resourceUri;
        $this->render = true;
      }

      if ($this->render){
        $ch = curl_init($init);
        $xtoken = 'X-Prerender-Token: ' . $this->token;
        curl_setopt($ch, CURLOPT_HTTPHEADER, array($xtoken));
        curl_setopt($ch, CURLOPT_USERAGENT, $agent);
        curl_setopt($ch, CURLOPT_HEADER, 0);            // No header in the result
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return, do not echo result

        /* Fetch and return content, save it. */
        $prerender = curl_exec($ch);
        curl_close($ch);
        $app->response->setBody($prerender);

      } else { /* Not coming from a bot, render as usual */
          $this->next->call();
      }
  }
}
?>
