<?php
namespace PoshmarkScripts;

use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Cookie;
use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use Facebook\WebDriver\Chrome\ChromeOptions;

class Script {
    use \PetrKnap\Php\Singleton\SingletonTrait;

    public $host;
    public $driver;
    public $capabilities;
    public $closets = [];
    private function __construct(){
        $this->create();
        $this->login($_SERVER['argv'][1], $_SERVER['argv'][2]);
        $this->findClosets();
        $this->shareClosets();
        $this->close();
    }
    public function shareClosets(){
        foreach($this->closets AS $closet){
            echo 'sharing closest: '.$closet . PHP_EOL;
            $this->driver->get('https://poshmark.com/closet/'.$closet);
            $this->driver->wait(10)->until(
                WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(
                    WebDriverBy::id('tiles-con')
                )
            );
            $products = $this->driver->findElements(
                WebDriverBy::cssSelector('#tiles-con > div')
            );
            foreach($products AS $product){
                echo 'sharing product.'.PHP_EOL;
                $product->findElement(
                    WebDriverBy::cssSelector('.share')
                )->click();
                $this->driver->wait(0.5);
                $this->driver->findElement(
                    WebDriverBy::cssSelector('#share-popup .pm-followers-share-link')
                )->click();
                $this->driver->wait(1);
            }
        }
    }

    public function findClosets() {
        $this->driver->wait(10)->until(
            WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(
                WebDriverBy::cssSelector('.feed__unit')
            )
        );
        $nextButton = $this->driver->findElement(
            WebDriverBy::cssSelector('.feed__unit .btn.btn--carousel.btn--carousel--next')
        );
        $peopleYouFollow = $this->driver->findElements(
            WebDriverBy::cssSelector('.feed__unit .feed__unit__content ul.carousel__slide li span.feed__carousel__item__text')
        );
        foreach($peopleYouFollow AS $person){
            if($person->getText() && $person->getText() !== '') $this->closets[] = $person->getText();
        }
        $nextButton->click();
        $this->driver->wait(1);

        $peopleYouFollow = $this->driver->findElements(
            WebDriverBy::cssSelector('.feed__unit .feed__unit__content ul.carousel__slide li span.feed__carousel__item__text')
        );
        foreach($peopleYouFollow AS $person){
            if($person->getText() && $person->getText() !== '') $this->closets[] = $person->getText();
        }
        echo 'Fetched closests. '.PHP_EOL;
    }
    public function login($username,$password){
        $this->driver->get('https://poshmark.com/login');
        // wait at most 10 seconds until at least one result is shown
        $this->driver->wait(10)->until(
            WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(
                WebDriverBy::cssSelector('#email-login-form')
            )
        );
        $login_field = $this->driver->findElement(
            WebDriverBy::id('login_form_username_email')
        );
        $password_field = $this->driver->findElement(
            WebDriverBy::id('login_form_password')
        );
        $login = $this->driver->findElement(
            WebDriverBy::cssSelector('#email-login-form button')
        );
        $login_field->sendKeys($username);
        $password_field->sendKeys($password);
        $login->click();
        $this->driver->wait(10)->until(
            WebDriverExpectedCondition::presenceOfAllElementsLocatedBy(
                WebDriverBy::cssSelector('.main__column')
            )
        );
        echo 'Login Success! '.PHP_EOL;
    }
    public function create() {
        // start Chrome with 5 second timeout
        $this->host = 'http://localhost:4444/wd/hub'; // this is the default
        $this->capabilities = DesiredCapabilities::chrome();
        //$options = new ChromeOptions();
        //$options->addArguments(array(
        //    '--headless',
        //));
        //$this->capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $this->driver = RemoteWebDriver::create($this->host, $this->capabilities, 5000);
    }
    public function close(){
        // close the browser
        $this->driver->quit();
    }
}