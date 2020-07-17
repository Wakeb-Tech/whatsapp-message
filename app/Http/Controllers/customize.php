<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use App\User;
use App\Choice;
use App\Lang;
use Twilio\TwiML\MessagingResponse;
use DB;


class WhatsController extends Controller
{
   

    public $from = '';
    public $body = '';
    public $img = '';
    public $vedio = '';
    public $xfile = '';
    public $url = '';
    public $final_text = '';
    public $content = '';
    public $count = 0;
    public $final_choses = '';
    public $res = '';
    public $data_lang = '';
    public $final_lat = '';
    public $final_long = '';


   public function listenToLeader(Request $request,$id)
    {
        
        $this->from = $request->input('From');
        $body = $request->input('Body');
        // $lat = $request->input('Latitude');
        // $lon = $request->input('Longitude');

        $data = DB::table('leaderExpress')->where('phone', $this->from)->first();
        if(!$data) {  
          
           $languages = [];
           $text = ['خدماتنا التقنية', 'حلول ومنتجات تقنية','طريقتنا في العمل'];
            
            foreach ($text as $te) {
            $number = $this->count++;
                DB::table('leaderExpress')->insert([
                    
                    'phone' => $this->from,
                    'body' => $te,
                    'choice_num' => $number
                ]);
                
               $languages[]= $number."-".$te;
            }
            
            
        
 
        $this->final_text = "\n *مرحباً بك نحن ملتزمون بدعم الابتكار الوطني، وذلك من خلال الدخول في شراكات مع القطاع الحكومي من أجل بناء خدمات رقمية تتسم بالاستدامة الذاتية وتحسين جودة الحياة نحن مستعدون للقادم، هل انت مستعد للحصول على خدماتنا ؟* " ."\n \n \n". implode("\n",$languages);
        $this->img = 'https://i.imgur.com/VKYe3SB.jpg';

        $this->respondToMessage();
        
         } else { // end if data


          $this->res =  $this->IncomigMessageAnalysis('leaderExpress',$this->from,$body);
          $langs = ['إنهاء المحادثة','القائمة الرئيسية'];
            
            if(in_array($this->res, $langs)) {
                
            if($this->res== 'القائمة الرئيسية' ) {
                    
               $languages = [];
               $text =  ['خدماتنا التقنية', 'حلول ومنتجات تقنية','طريقتنا في العمل'];
                foreach ($text as $te) {
                $number = $this->count++;
                    DB::table('leaderExpress')->insert([
                        
                        'phone' => $this->from,
                        'body' => $te,
                        'choice_num' => $number
                    ]);
                    
                   $languages[]= $number."-".$te;
                }

               $this->final_text = "\n *مرحباً بك نحن ملتزمون بدعم الابتكار الوطني، وذلك من خلال الدخول في شراكات مع القطاع الحكومي من أجل بناء خدمات رقمية تتسم بالاستدامة الذاتية وتحسين جودة الحياة نحن مستعدون للقادم، هل انت مستعد للحصول على خدماتنا ؟* " ."\n \n \n". implode("\n",$languages);
               $this->img = 'https://i.imgur.com/VKYe3SB.jpg';
                    
            } elseif ($this->res== 'إنهاء المحادثة') {
                $languages = [];
                $text =  ['القائمة الرئيسية'];
                foreach ($text as $te) {
                $number = $this->count++;
                    DB::table('leaderExpress')->insert([
                        
                        'phone' => $this->from,
                        'body' => $te,
                        'choice_num' => $number
                    ]);
                    
                   $languages[]= $number."-".$te;
                }

               $this->final_text = "\n نسعد بخدمتكم دائماً 😊 " ."\n". implode("\n",$languages);
            }

               
                
            } else {// end in_array 

        
            $result = $this->apiResponse($this->res ,$id);
            if($result->meta->flag == 'greating' ||  $result->meta->flag == 'error') {
                    $this->final_text = $result->meta->message;
            } elseif($result->meta->flag== 'meanings')  {
                
                $choices = $result->avilable_options;

                if($choices) { 
                    
                  
                    $chooses = []; 
                    $chnum = 0;
                    $first = true;
                    foreach ($choices as $value) {
                        
                        if($value->an_text != '') {
                        
                        $chnum ++;
                        
                        $choice =(string) strip_tags($value->an_text);
                        $choic_string = htmlentities($choice, null, 'utf-8');
                        $choic_beauty = str_replace("&amp;nbsp;", " ", $choic_string);
                        $choic_content = html_entity_decode($choic_beauty);
                        $number = $this->count++;
                        
                        if($choic_content == 'Back  to main menu' || $choic_content == 'Back to main menu' || $choic_content == 'العودة إلى القائمة الرئيسية' || $choic_content == 'العودة إلى القائمة السابقة' ) {
                            
                            
                                if ( $first )
                                {
                                    DB::table('leaderExpress')->insert([
                                    'phone' => $this->from,
                                    'body' => $choic_content,
                                    'choice_num' => $number
                                ]);
                                
                                $chooses[]= $number."-".$choic_content;
                                            
                                    $first = false;
                                }
                            
                        } else {
                            DB::table('leaderExpress')->insert([
                            
                            'phone' => $this->from,
                            'body' => $choic_content,
                            'choice_num' => $number
                            ]);
                        
                            $chooses[]= $number."-".$choic_content;
                            
                        }
                        
                        
                        

                       }
                        
                    }
                    if($chnum > 0) {
                        $checklang = Lang::where('phone', $this->from)->first();
                         if($checklang ){
                             $this->data_lang = $checklang->lang;
                         }
             
                        if($this->data_lang == 'en')  {

                            $this->final_choses = "\n Please Enter the  right number " ."\n". implode("\n",$chooses);

                        }else {
                        $this->final_choses = "\n أجب بالرقم المناسب للإختيار" ."\n". implode("\n",$chooses); 
                            
                        }
                    }

                }// end if choices 
                
                 $this->final_text = $this->final_choses."\n";

                
            } elseif($result->meta->flag == 'normal' ){
                
                $text = $result->question[0]->text ;
                if($text) {

                    $origImageSrc = [];
                    preg_match_all('/<img[^>]+>/i',$text, $imgs); 
                    for ($i = 0; $i < count($imgs[0]); $i++) {
                      preg_match('/src="([^"]+)/i',$imgs[0][$i], $photo);
                      $origImageSrc[] = str_ireplace( 'src="', '',  $photo[0]);
                    }
                      $this->img = implode(" ",$origImageSrc);


                    $link = [];
                    preg_match_all('/<a[^>]+>/i',$text, $paths); 
                    for ($i = 0; $i < count($paths[0]); $i++) {
                      preg_match('/href="([^"]+)/i',$paths[0][$i], $url);
                      $link[] = str_ireplace( 'href="', '',  $url[0]);
                    }
                      $aurl = implode(" ",$link);
                      
                      $last = preg_match("/http:\/\/.*?\.pdf\b/i", $aurl);
                      if ($last === 1 ) {
                          $this->xfile = $aurl;
                      }else {
                          $this->url = $aurl;
                      }
                      
                    $origVedioSrc = [];
                    preg_match_all('/<iframe[^>]+>/i',$text, $vedios); 
                    for ($i = 0; $i < count($vedios[0]); $i++) {
                      preg_match('/src="([^"]+)/i',$vedios[0][$i], $ved);
                      $origVedioSrc[] = str_ireplace( 'src="', '',  $ved[0]);
                    }
                     $vedio_string = implode(" ",$origVedioSrc);
                     $this->vedio =  str_ireplace( '//www.youtube.com/embed/' , 'https://youtu.be/' , $vedio_string);

                    $cordinates = [];
                    preg_match_all('/<iframe[^>]+>/i',$text, $cordins); 
                    for ($i = 0; $i < count($cordins[0]); $i++) {
                      preg_match('/loc="([^"]+)/i',$cordins[0][$i], $cord);
                      $cordinates[] = str_ireplace( 'loc="', '',  $cord[0]);
                    }

                     $location_string = implode(" ",$cordinates);
                     $locations = explode("," , $location_string);
                     $this->final_lat = $locations[0];
                     $this->final_long = end($locations);

          
                    $txt =(string) strip_tags($text);

                    $string = htmlentities($txt, null, 'utf-8');
                    $beauty = str_replace("&amp;nbsp;", " ", $string);
                    $this->content = html_entity_decode($beauty);


                } // end if text

                $choices = $result->avilable_options;

                if($choices) { 
                    
                  
                    $chooses = []; 
                    $chnum = 0;
                    foreach ($choices as $value) {
                        
                        if($value->an_text != '') {
                        $chnum ++;
                        
                        $choice =(string) strip_tags($value->an_text);
                        $choic_string = htmlentities($choice, null, 'utf-8');
                        $choic_beauty = str_replace("&amp;nbsp;", " ", $choic_string);
                        $choic_content = html_entity_decode($choic_beauty);
                        $number = $this->count++;
                        
                        
                        DB::table('leaderExpress')->insert([
                                    'phone' => $this->from,
                                    'body' => $choic_content,
                                    'choice_num' => $number
                                ]);
                                

                        $chooses[]= $number."-".$choic_content;

                       }
                        
                    }
         
                     if($chnum > 0) {
                         $checklang = Lang::where('phone', $this->from)->first();
                         if($checklang ){
                             $this->data_lang = $checklang->lang;
                         }
                         
                         if($this->data_lang == 'en')  {

                            $this->final_choses = "\n Please Enter the  right number " ."\n". implode("\n",$chooses);

                        }else {
                        $this->final_choses = "\n أجب بالرقم المناسب للإختيار" ."\n". implode("\n",$chooses); 
                            
                        }
                       }

                }// end if choices 

                

            $this->final_text = $this->content. "\n" .$this->vedio. "\n" .$this->url."\n". $this->final_choses."\n";
                
            } // end elseif normal
        
            
          }// end else  in_array  
          if($this->final_lat != '' && $this->final_long != '')
          {
            $this->respondWithMap();
          }else {

             $this->respondToMessage();
          }

         } // end else id data
       

    }// end listenToTamkeen 


    public function respondToMessage() {
        $response = new MessagingResponse();
        $message = $response->message('');
        
        $message->body($this->final_text);
        if($this->img != ''){
         $message->media($this->img);
        }
        if($this->xfile != ''){
         $message->media($this->xfile);
        }

        print $response;
    } // end respondToMessage

    public function respondWithMap() {

      $sid    = "AC207c8547402a17a93276435290565827";
        $token  = "1e79b31d479d22e43188da22e989c945";
        $twilio = new Client($sid, $token);
        $message = $twilio->messages
                  ->create($this->from, // to
                           [
                               "from" => "whatsapp:+19706395866",
                               "body" => $this->final_text,
                               "persistentAction" => ["geo:".$this->final_lat.",".$this->final_long]
                               
                           ]
                  );

        print($message->sid);
    } // end respondWithMap




   public function IncomigMessageAnalysis($table,$from,$body) {
    
       $result = DB::table('leaderExpress')->where('phone', $from)->where('choice_num', $body)->orderBy('id', 'desc')->first();
        if($result) {
            return $result->body;
        } else {
            return $body ;
        }
    }// end IncomigMessageAnalysis
   
}
