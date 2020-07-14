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
       


   public function listenToReplies(Request $request, $id)
    {
        $from = $request->input('From');
        $body = $request->input('Body');
        
        $this->res =  $this->analysis($from,$body);
        
        $result = $this->apiResponse($this->res ,$id);
        
       
        
        if($result->meta->flag == 'greating' ||  $result->meta->flag == 'error') {
                $this->final_text = $result->meta->message;
        } elseif($result->meta->flag== 'meanings')  {
            
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
                    
                    DB::table('choices')->insert([
                        
                        'phone' => $from,
                        'body' => $choic_content,
                        'choice_num' => $number
                    ]);
                    
                    $chooses[]= $number."-".$choic_content;
                    

                   }
                    
                }
                if($chnum > 0) {
                $this->final_choses = "\n أجب بالرقم المناسب للإختيار" ."\n". implode("\n",$chooses);
                }

            }// end if choices 
            
             $this->final_text = $this->final_choses."\n";

            
        } elseif($result->meta->flag == 'normal' ){
            
            $text = $result->question[0]->text ;
            if($text) {

                $origImageSrc = [];
                preg_match_all('/<img[^>]+>/i',$text, $tags); 
                for ($i = 0; $i < count($tags[0]); $i++) {
                  preg_match('/src="([^"]+)/i',$tags[0][$i], $tag);
                  $origImageSrc[] = str_ireplace( 'src="', '',  $tag[0]);
                }
                  $this->img = implode(" ",$origImageSrc);


                $link = [];
                preg_match_all('/<a[^>]+>/i',$text, $tags); 
                for ($i = 0; $i < count($tags[0]); $i++) {
                  preg_match('/href="([^"]+)/i',$tags[0][$i], $tag);
                  $link[] = str_ireplace( 'href="', '',  $tag[0]);
                }
                  $this->url = implode(" ",$link);

      
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
                    
                    
                    
                    $ch = new Choice(); 
                    $ch->phone = $from;
                    $ch->body  = $choic_content;
                    $ch->choice_num = $this->count++;
                    $ch->save();

                    $chooses[]= $ch->choice_num."-".$ch->body;

                   }
                    
                }
                if($chnum > 0) {
                $this->final_choses = "\n أجب بالرقم المناسب للإختيار" ."\n". implode("\n",$chooses);
                }

            }// end if choices 

            

        $this->final_text = $this->content. "\n" .$this->url."\n". $this->final_choses."\n";
            
        } // end elseif normal
        
            

            

        $response = new MessagingResponse();
        $message = $response->message('');
        
        $message->body($this->final_text);
        if($this->img != ''){
         $message->media($this->img);
        }

        print $response;


    } // end listentoreplies
    
    public function listenToRch(Request $request,$id)
    {
        
        $from = $request->input('From');
        $body = $request->input('Body');
    
        $data = Choice::where('phone', $from)->first();
        
       
        if(!$data) {  
          
           $languages = [];
           $langs = ['Arabic','English'];
            foreach ($langs as $lang) {
            $number = $this->count++;
                DB::table('choices')->insert([
                    
                    'phone' => $from,
                    'body' => $lang,
                    'choice_num' => $number
                ]);
                
               $languages[]= $number."-".$lang;
            }
            
            
        
 
        $this->final_text = "\n Please Enter The Number Of Your language " ."\n". implode("\n",$languages);
        
        
         } else { // end if data


            $this->res =  $this->analysis($from,$body);
          $langs = ['Arabic','English', 'Back  to main menu','9', 'Back to main menu','العودة إلى القائمة الرئيسية','العودة إلى القائمة السابقة','العودة الي القائمة الرئيسية' ];
            
            if(in_array($this->res, $langs)) {
         
                if($this->res == 'Arabic') {

                    DB::table('langs')->insert([
                        'phone' => $from,
                        'lang' => 'ar'
                    ]);
                    $result = $this->entryResponse('ar');
                }elseif($this->res == 'English') { 
                    DB::table('langs')->insert([
                        'phone' => $from,
                        'lang' => 'en'
                    ]);
                    $result = $this->entryResponse('en');
                } elseif($this->res == '9' || $this->res =='Back to main menu'|| $this->res =='Back  to main menu'|| $this->res =='العودة الي القائمة الرئيسية'|| $this->res =='العودة إلى القائمة الرئيسية' || $this->res =='العودة إلى القائمة السابقة'){
                    $checklang = Lang::where('phone', $from)->latest()->first();
                     if($checklang ){
                         $this->data_lang = $checklang->lang;
                     }
                   
                    $result = $this->entryResponse($this->data_lang);


                }

                
            }else{// end in_array 

                 $result = $this->apiResponse($this->res ,$id);

            }

        
           
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
                                    DB::table('choices')->insert([
                                    'phone' => $from,
                                    'body' => $choic_content,
                                    'choice_num' => $number
                                ]);
                                
                                $chooses[]= $number."-".$choic_content;
                                            
                                    $first = false;
                                }
                            
                        } else {
                            DB::table('choices')->insert([
                            
                            'phone' => $from,
                            'body' => $choic_content,
                            'choice_num' => $number
                            ]);
                        
                            $chooses[]= $number."-".$choic_content;
                            
                        }
                        
                        
                        

                       }
                        
                    }
                    if($chnum > 0) {
                        $checklang = Lang::where('phone', $from)->first();
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
                    preg_match_all('/<img[^>]+>/i',$text, $tags); 
                    for ($i = 0; $i < count($tags[0]); $i++) {
                      preg_match('/src="([^"]+)/i',$tags[0][$i], $tag);
                      $origImageSrc[] = str_ireplace( 'src="', '',  $tag[0]);
                    }
                      $this->img = implode(" ",$origImageSrc);


                    $link = [];
                    preg_match_all('/<a[^>]+>/i',$text, $tags); 
                    for ($i = 0; $i < count($tags[0]); $i++) {
                      preg_match('/href="([^"]+)/i',$tags[0][$i], $tag);
                      $link[] = str_ireplace( 'href="', '',  $tag[0]);
                    }
                      $this->url = implode(" ",$link);

          
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
                        
                        
                        
                        $ch = new Choice(); 
                        $ch->phone = $from;
                        $ch->body  = $choic_content;
                        $ch->choice_num = $this->count++;
                        $ch->save();

                        $chooses[]= $ch->choice_num."-".$ch->body;

                       }
                        
                    }
         
                     if($chnum > 0) {
                         $checklang = Lang::where('phone', $from)->first();
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

                

            $this->final_text = $this->content. "\n" .$this->url."\n". $this->final_choses."\n";
                
            } // end elseif normal
        
            
            
         } // end else id data
       

        $response = new MessagingResponse();
        $message = $response->message('');
        
        $message->body($this->final_text);
        if($this->img != ''){
         $message->media($this->img);
        }

        print $response;


    }// end listenToRch
    
    public function listenToWakeb(Request $request,$id)
    {
        
        $from = $request->input('From');
        $body = $request->input('Body');
    
        $data = Choice::where('phone', $from)->first();
        
       
        if(!$data) {  
          
           $languages = [];
           $text = ['خدمات واكب','المنتجات'];
            foreach ($text as $te) {
            $number = $this->count++;
                DB::table('choices')->insert([
                    
                    'phone' => $from,
                    'body' => $te,
                    'choice_num' => $number
                ]);
                
               $languages[]= $number."-".$te;
            }
            
            
        
 
        $this->final_text = "\n مرحبا بكم في Wakeb Data . انت الأن تتحدث مع  مجيب ( المساعد الذكي لشركة واكب ) يمكننا تزويد عملك بأحدث تقنيات الذكاء الإصطناعي في حزمة متكاملة لجميع أحجام المنشأت والشركات  تعرف علي خدمات وتقنيات الذكاء الإصطناعي . " ."\n". implode("\n",$languages);
        $this->img = 'http://wakeb.tech/assets/images/intro.png';
        
         } else { // end if data


          $this->res =  $this->analysis($from,$body);
          $langs = ['القائمة الرئيسية'];
            
            if(in_array($this->res, $langs)) {

               $languages = [];
               $text = ['خدمات واكب','المنتجات'];
                foreach ($text as $te) {
                $number = $this->count++;
                    DB::table('choices')->insert([
                        
                        'phone' => $from,
                        'body' => $te,
                        'choice_num' => $number
                    ]);
                    
                   $languages[]= $number."-".$te;
                }

               $this->final_text = "\n  مرحبا بكم في Wakeb Data . انت الأن تتحدث مع  مجيب ( المساعد الذكي لشركة واكب ) يمكننا تزويد عملك بأحدث تقنيات الذكاء الإصطناعي في حزمة متكاملة لجميع أحجام المنشأت والشركات  تعرف علي خدمات وتقنيات الذكاء الإصطناعي .  " ."\n". implode("\n",$languages);
               $this->img = 'http://wakeb.tech/assets/images/intro.png';
               
                
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
                                    DB::table('choices')->insert([
                                    'phone' => $from,
                                    'body' => $choic_content,
                                    'choice_num' => $number
                                ]);
                                
                                $chooses[]= $number."-".$choic_content;
                                            
                                    $first = false;
                                }
                            
                        } else {
                            DB::table('choices')->insert([
                            
                            'phone' => $from,
                            'body' => $choic_content,
                            'choice_num' => $number
                            ]);
                        
                            $chooses[]= $number."-".$choic_content;
                            
                        }
                        
                        
                        

                       }
                        
                    }
                    if($chnum > 0) {
                        $checklang = Lang::where('phone', $from)->first();
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
                    preg_match_all('/<img[^>]+>/i',$text, $tags); 
                    for ($i = 0; $i < count($tags[0]); $i++) {
                      preg_match('/src="([^"]+)/i',$tags[0][$i], $tag);
                      $origImageSrc[] = str_ireplace( 'src="', '',  $tag[0]);
                    }
                      $this->img = implode(" ",$origImageSrc);


                    $link = [];
                    preg_match_all('/<a[^>]+>/i',$text, $tags); 
                    for ($i = 0; $i < count($tags[0]); $i++) {
                      preg_match('/href="([^"]+)/i',$tags[0][$i], $tag);
                      $link[] = str_ireplace( 'href="', '',  $tag[0]);
                    }
                      $aurl = implode(" ",$link);
                      
                      $last = preg_match("/http:\/\/.*?\.pdf\b/i", $aurl);
                      if ($last === 1 ) {
                          $this->xfile = $aurl;
                      }else {
                          $this->url = $aurl;
                      }
                      
                    $origVedioSrc = [];
                    preg_match_all('/<iframe[^>]+>/i',$text, $tags); 
                    for ($i = 0; $i < count($tags[0]); $i++) {
                      preg_match('/src="([^"]+)/i',$tags[0][$i], $tag);
                      $origVedioSrc[] = str_ireplace( 'src="', '',  $tag[0]);
                    }
                     $vedio_string = implode(" ",$origVedioSrc);
                     $this->vedio =  str_ireplace( '//www.youtube.com/embed/' , 'https://youtu.be/' , $vedio_string);

                    

          
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
                        
                        if($value->an_text != '' && $value->an_text != 'None') {
                        $chnum ++;
                        
                        $choice =(string) strip_tags($value->an_text);
                        $choic_string = htmlentities($choice, null, 'utf-8');
                        $choic_beauty = str_replace("&amp;nbsp;", " ", $choic_string);
                        $choic_content = html_entity_decode($choic_beauty);
                        
                        
                        
                        $ch = new Choice(); 
                        $ch->phone = $from;
                        $ch->body  = $choic_content;
                        $ch->choice_num = $this->count++;
                        $ch->save();

                        $chooses[]= $ch->choice_num."-".$ch->body;

                       }
                        
                    }
         
                     if($chnum > 0) {
                         $checklang = Lang::where('phone', $from)->first();
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
        
            
          }
         } // end else id data
       

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


    }// end listenWakeb
    
    public function listenToMajd(Request $request,$id)
    {
        
        $from = $request->input('From');
        $body = $request->input('Body');
    
        // $data = Choice::where('phone', $from)->first();
        $data = DB::table('majdouie_sms')->where('phone', $from)->first();
        
       
        if(!$data) {  
          
           $languages = [];
           $text =  ['أوقات العمل','العروض','العلامات التجارية', 'الصيانة','المعرض الإفتراضي','الدخول إلي برنامج سبارك'];
            foreach ($text as $te) {
            $number = $this->count++;
                DB::table('majdouie_sms')->insert([
                    
                    'phone' => $from,
                    'body' => $te,
                    'choice_num' => $number
                ]);
                
               $languages[]= $number."-".$te;
            }
            
            
        
 
        $this->final_text = "\n *مرحباً بكم في المجدوعي للسيارات إن لدينا إصراراً واضحاً لبناء مستقبل أفضل لمؤسستنا وذلك عن طريق التحسين المستمر وتفاعلنا مع عملائنا و شركائنا وموظفينا عن طريق تقديم منتجات ذات جودة عالية وخدمات على مستوى عالٍ كيف يمكننا خدمتك؟*. " ."\n". implode("\n",$languages);
        $this->img = 'http://wakeb.tech/assets/images/majdouie.jpg';
        
         } else { // end if data


          $this->res =  $this->MajdouieAnalysis($from,$body);
          $langs = ['إنهاء المحادثة','القائمة الرئيسية'];
            
            if(in_array($this->res, $langs)) {
                
            if($this->res== 'القائمة الرئيسية' ) {
                    
               $languages = [];
               $text = ['أوقات العمل','العروض','العلامات التجارية', 'الصيانة','المعرض الإفتراضي','الدخول إلي برنامج سبارك'];
                foreach ($text as $te) {
                $number = $this->count++;
                    DB::table('majdouie_sms')->insert([
                        
                        'phone' => $from,
                        'body' => $te,
                        'choice_num' => $number
                    ]);
                    
                   $languages[]= $number."-".$te;
                }

               $this->final_text = "\n  *مرحباً بكم في المجدوعي للسيارات إن لدينا إصراراً واضحاً لبناء مستقبل أفضل لمؤسستنا وذلك عن طريق التحسين المستمر وتفاعلنا مع عملائنا و شركائنا وموظفينا عن طريق تقديم منتجات ذات جودة عالية وخدمات على مستوى عالٍ كيف يمكننا خدمتك؟* " ."\n". implode("\n",$languages);
               $this->img = 'http://wakeb.tech/assets/images/majdouie.jpg';
                    
            } elseif ($this->res== 'إنهاء المحادثة') {
                $languages = [];
                $text =  ['القائمة الرئيسية'];
                foreach ($text as $te) {
                $number = $this->count++;
                    DB::table('majdouie_sms')->insert([
                        
                        'phone' => $from,
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
                                    DB::table('majdouie_sms')->insert([
                                    'phone' => $from,
                                    'body' => $choic_content,
                                    'choice_num' => $number
                                ]);
                                
                                $chooses[]= $number."-".$choic_content;
                                            
                                    $first = false;
                                }
                            
                        } else {
                            DB::table('majdouie_sms')->insert([
                            
                            'phone' => $from,
                            'body' => $choic_content,
                            'choice_num' => $number
                            ]);
                        
                            $chooses[]= $number."-".$choic_content;
                            
                        }
                        
                        
                        

                       }
                        
                    }
                    if($chnum > 0) {
                        $checklang = Lang::where('phone', $from)->first();
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
                    preg_match_all('/<img[^>]+>/i',$text, $tags); 
                    for ($i = 0; $i < count($tags[0]); $i++) {
                      preg_match('/src="([^"]+)/i',$tags[0][$i], $tag);
                      $origImageSrc[] = str_ireplace( 'src="', '',  $tag[0]);
                    }
                      $this->img = implode(" ",$origImageSrc);


                    $link = [];
                    preg_match_all('/<a[^>]+>/i',$text, $tags); 
                    for ($i = 0; $i < count($tags[0]); $i++) {
                      preg_match('/href="([^"]+)/i',$tags[0][$i], $tag);
                      $link[] = str_ireplace( 'href="', '',  $tag[0]);
                    }
                      $this->url = implode(" ",$link);

          
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
                        
                        
                        DB::table('majdouie_sms')->insert([
                                    'phone' => $from,
                                    'body' => $choic_content,
                                    'choice_num' => $number
                                ]);
                                

                        $chooses[]= $number."-".$choic_content;

                       }
                        
                    }
         
                     if($chnum > 0) {
                         $checklang = Lang::where('phone', $from)->first();
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

                

            $this->final_text = $this->content. "\n" .$this->url."\n". $this->final_choses."\n";
                
            } // end elseif normal
        
            
          }
         } // end else id data
       

        $response = new MessagingResponse();
        $message = $response->message('');
        
        $message->body($this->final_text);
        if($this->img != ''){
         $message->media($this->img);
        }

        print $response;


    }// end listenToMajd
    
    
    public function listenToTamken(Request $request,$id)
    {
        
        $from = $request->input('From');
        $body = $request->input('Body');
    
        // $data = Choice::where('phone', $from)->first();
        $data = DB::table('tamken_sms')->where('phone', $from)->first();
        
       
        if(!$data) {  
          
           $languages = [];
           $text = ['خدماتنا التقنية', 'حلول ومنتجات تقنية','طريقتنا في العمل'];
            
            foreach ($text as $te) {
            $number = $this->count++;
                DB::table('tamken_sms')->insert([
                    
                    'phone' => $from,
                    'body' => $te,
                    'choice_num' => $number
                ]);
                
               $languages[]= $number."-".$te;
            }
            
            
        
 
        $this->final_text = "\n *مرحباً بك نحن ملتزمون بدعم الابتكار الوطني، وذلك من خلال الدخول في شراكات مع القطاع الحكومي من أجل بناء خدمات رقمية تتسم بالاستدامة الذاتية وتحسين جودة الحياة نحن مستعدون للقادم، هل انت مستعد للحصول على خدماتنا ؟* " ."\n \n \n". implode("\n",$languages);
        $this->img = 'https://i.imgur.com/VKYe3SB.jpg';
        
         } else { // end if data


          $this->res =  $this->TamkenAnalysis($from,$body);
          $langs = ['إنهاء المحادثة','القائمة الرئيسية'];
            
            if(in_array($this->res, $langs)) {
                
            if($this->res== 'القائمة الرئيسية' ) {
                    
               $languages = [];
               $text =  ['خدماتنا التقنية', 'حلول ومنتجات تقنية','طريقتنا في العمل'];
                foreach ($text as $te) {
                $number = $this->count++;
                    DB::table('tamken_sms')->insert([
                        
                        'phone' => $from,
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
                    DB::table('tamken_sms')->insert([
                        
                        'phone' => $from,
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
                                    DB::table('tamken_sms')->insert([
                                    'phone' => $from,
                                    'body' => $choic_content,
                                    'choice_num' => $number
                                ]);
                                
                                $chooses[]= $number."-".$choic_content;
                                            
                                    $first = false;
                                }
                            
                        } else {
                            DB::table('tamken_sms')->insert([
                            
                            'phone' => $from,
                            'body' => $choic_content,
                            'choice_num' => $number
                            ]);
                        
                            $chooses[]= $number."-".$choic_content;
                            
                        }
                        
                        
                        

                       }
                        
                    }
                    if($chnum > 0) {
                        $checklang = Lang::where('phone', $from)->first();
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
                    preg_match_all('/<img[^>]+>/i',$text, $tags); 
                    for ($i = 0; $i < count($tags[0]); $i++) {
                      preg_match('/src="([^"]+)/i',$tags[0][$i], $tag);
                      $origImageSrc[] = str_ireplace( 'src="', '',  $tag[0]);
                    }
                      $this->img = implode(" ",$origImageSrc);
                      
                    $origVedioSrc = [];
                    preg_match_all('/<iframe[^>]+>/i',$text, $tags); 
                    for ($i = 0; $i < count($tags[0]); $i++) {
                      preg_match('/src="([^"]+)/i',$tags[0][$i], $tag);
                      $origVedioSrc[] = str_ireplace( 'src="', '',  $tag[0]);
                    }
                     $vedio_string = implode(" ",$origVedioSrc);
                     $this->vedio =  str_ireplace( '//' , 'https://' , $vedio_string);
                    

                    $link = [];
                    preg_match_all('/<a[^>]+>/i',$text, $tags); 
                    for ($i = 0; $i < count($tags[0]); $i++) {
                      preg_match('/href="([^"]+)/i',$tags[0][$i], $tag);
                      $link[] = str_ireplace( 'href="', '',  $tag[0]);
                    }
                      $this->url = implode(" ",$link);

          
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
                        
                        
                        DB::table('tamken_sms')->insert([
                                    'phone' => $from,
                                    'body' => $choic_content,
                                    'choice_num' => $number
                                ]);
                                

                        $chooses[]= $number."-".$choic_content;

                       }
                        
                    }
         
                     if($chnum > 0) {
                         $checklang = Lang::where('phone', $from)->first();
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

                

            $this->final_text = $this->content. "\n" .$this->url."\n". $this->final_choses."\n";
                
            } // end elseif normal
        
            
          }
         } // end else id data
       

        $response = new MessagingResponse();
        $message = $response->message('');
        
        $message->body($this->final_text);
        if($this->img != ''){
         $message->media($this->img);
        }
        if($this->vedio != ''){
         $message->media($this->vedio);
        }

        print $response;


    }// end listenToTamkeen
    
    
    
    public function analysis($from,$body) {
    
       $result = Choice::where('phone', $from)->where('choice_num', $body)->orderBy('id', 'desc')->first();
        if($result) {
            return $result->body;
        } else {
            return $body ;
        }
    }// end analysis
    
    public function MajdouieAnalysis($from,$body) {
    
       $result = DB::table('majdouie_sms')->where('phone', $from)->where('choice_num', $body)->orderBy('id', 'desc')->first();
        if($result) {
            return $result->body;
        } else {
            return $body ;
        }
    }// end MajdouieAnalysis
    
    public function TamkenAnalysis($from,$body) {
    
       $result = DB::table('tamken_sms')->where('phone', $from)->where('choice_num', $body)->orderBy('id', 'desc')->first();
        if($result) {
            return $result->body;
        } else {
            return $body ;
        }
    }// end TamkenAnalysis
    
    public function entry($from,$body) {
    
        $result = Choice::where('phone', $from)->first();
        if($result) {
            $data =  $this->analysis($from,$body);
            return $data;
        } else {
            
            return $body ;
        }
    }// end analysis
    
    
    public function apiResponse($text,$chat_id)
    {

        $data = array(
            'text' => $text,
            'chat_id' => $chat_id,
            
        );
        $url = 'https://chatmatch-api.azurewebsites.net/chatbot/live';

        //create a new cURL resource
        $ch = curl_init($url);

        $payload = json_encode($data);
        //attach encoded JSON string to the POST fields
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        //set the content type to application/json
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        //return response instead of outputting
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //execute the POST request
        $result = curl_exec($ch);
        return json_decode($result);
        // return response()->json($result);
        // return Response::json($result);
    }


     public function entryResponse($lang)
    {

        $cURLConnection = curl_init();
        curl_setopt($cURLConnection, CURLOPT_URL, 'http://mujib-chatbot.com/chatbot/rchrch/37/entry_question?id=entry&lang='.$lang.'&chat=37&status=1');
        curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($cURLConnection);
        curl_close($cURLConnection);
      
        return json_decode($result);
   
    }

    
    
     public function test()
    {
        
        $result = $this->apiResponse('video','64');
    //   $txt =  (string) strip_tags($result->question[0]->text);
    //   $string = htmlentities($txt, null, 'utf-8');
    //     $beauty = str_replace("&amp;nbsp;", " ", $string);
    //   $buk = html_entity_decode($beauty);
        // $result =  ['الدخول إلى برنامج عماد','العروض','العلامات التجارية', 'الصيانة','المعرض الإفتراضي','الدخول إلي برنامج سبارك'];
       
        // dd($result->question[0]->text);
        
        // $from = 'whatsapp:+201093565730';
        // $body = '1';
        
        // $result = Choice::where('phone', $from)->where('choice_num', $body)->orderBy('id', 'desc')->first();
        // $result = Choice::where('phone', $from)->where('choice_num', $body)->latest()->first();
     dd($result);
    }
    
     public function arabic()
    {
        
        //  $langs = ['Arabic','English', 'Back  to main menu','9', 'Back to main menu','العودة إلى القائمة الرئيسية','العودة إلى القائمة السابقة'
            // ];
            
            // $var = '<div>منصة مشاركة الملفات&nbsp &nbsp &nbsp &nbsp&nbsp</div><div><br></div><div><iframe frameborder="0" src="//www.youtube.com/embed/RIkn5fEyYfw" width="640" height="360" class="note-video-clip"></iframe><br></div><div><br></div><div>هي منصة مفتوحة المصدر تعتمد الحلول السحابية لضمان تخزين المعلومات ومشاركتها بأمان بدءًا من مستوى اعضاء الفريق الواحد وصولاً لمستوى الجهة الحكومية أو المؤسسة كاملاً، مع الحفاظ على البيانات داخل المملكة العربية السعودية. كما تعمل المنصة على تعزيز وتسهيل نقل المعلومات والبيانات داخل المنظمة او الجهات التي تتعامل معها عن طريق تمكينهم من مشاركة الملفات الخاصة والروابط بأمان وخصوصية بالإضافة الى تحديد مستوى الصلاحيات والتشفير مع إمكانية التعديل عليها.</div><div><br></div><div><a href="https://filesharing.tamkeentech.sa/" target="_blank">زيارة الموقع</a></div>';
        
                   $text = '<p>Profile<a href="http://wakeb.tech/assets/images/Infograpic%20whatsApp%20Solutions.pdf" target="_blank"> </a></p>';
                   
                   
                   $link = [];
                    preg_match_all('/<a[^>]+>/i',$text, $tags); 
                    for ($i = 0; $i < count($tags[0]); $i++) {
                      preg_match('/href="([^"]+)/i',$tags[0][$i], $tag);
                      $link[] = str_ireplace( 'href="', '',  $tag[0]);
                    }
                      $aurl = implode(" ",$link);
                      
                      $last = preg_match("/http:\/\/.*?\.pdf\b/i", $aurl);
                      if ($last === 1 ) {
                          $this->xfile = $aurl;
                      }else {
                          $this->url = $aurl;
                      }
                     
                    // $last=  str_ireplace( '//' , 'https://' , $langs);
        dd($last);

    }

    
   

    
   
}
