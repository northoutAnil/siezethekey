<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require APPPATH.'/libraries/REST_Controller.php';
class User extends REST_Controller {

	public function __construct(){
		parent::__construct();
		$this->load->helper('response');
		$this->load->helper('common');
		$this->load->library('twilio_sms_library');
		$this->load->model('user_model');
		$this->load->model('image_model');
		$this->load->model('notification_model');
		$this->lang->load('english', 'english');
		
		
	}
	
	public function index()
	{
		$this->load->view('welcome_message');
		//echo '<pre>';print_r($_SERVER);
	}
	
	/***
	*generate token and send to user
	*@post
	**********/
	public function sendSms_post(){
		$this->form_validation->set_rules('number','Contact Number','required|max_length[15]|min_length[10]');
		if($this->form_validation->run()==TRUE){
			$number = $this->post('number');
			$data = $this->user_model->tokenGenerate($number);
			$this->response($data);
		}else{
			$this->response(response_fail('FAILED',  strip_tags(validation_errors())));
		}
		
		
		
	}
	
	
	
	
	
	
	/****
	*verify token and register user
	*@post
	****/
	
	public function checkToken_post(){
		$this->form_validation->set_rules('number','Contact Number','required|max_length[15]|min_length[10]');
		$this->form_validation->set_rules('deviceid','Device Id','required');
		//$this->form_validation->set_rules('profile_pic','Profile Pic','required');
		$this->form_validation->set_rules('token','Token','required|exact_length[6]');
		if($this->form_validation->run()==TRUE){
			$number = $this->post('number');
			$token =  $this->post('token');
			$deviceid = $this->post('deviceid');
		
			$data = $this->user_model->checkToken($number,$token);
			if($data == true){
				$user_data = $this->user_model->userRegister($number,$deviceid);
				
				if(!empty($user_data)){
				$this->response(response_success(array("user"=>"REGISTERED"),"SUCCESS", ""));
				}else{
					$this->response(response_fail('FAILED', "User Already Registered"));
				}
			}else{
				$this->response(response_fail('FAILED', "Invalid Token"));	
			}
			
		}else{
			$this->response(response_fail('FAILED', strip_tags(validation_errors())));
		}
		
		
	}
	
	
	/******
	 *user login
	 *@post
	 *
	 */
	
	public function userLogin_post(){
		 $this->form_validation->set_rules('number','Contact Number','required|max_length[15]|min_length[10]');
		if($this->form_validation->run()==TRUE){
			$number = $this->post('number');
				$user_data = $this->user_model->userLogin($number);
				
				if($user_data){
						$this->response(response_success($user_data,"SUCCESS", ""));
				}else{
					$this->response(response_fail('FAILED',"Authentication faled"));
				}
		}else{
			$this->response(response_fail('FAILED',  strip_tags(validation_errors())));
		}
	}
	
	/***
	 *
	 *add own contact 
	 *@post
	 */
	
	public function contactAdd_post(){
		
		  $this->form_validation->set_rules('contactUserId','UserId','required');
		 $this->form_validation->set_rules('number','Contact Number','required|max_length[15]|min_length[10]');
		 $this->form_validation->set_rules('name','Name','required');
		
		if($this->form_validation->run()==TRUE){
			$contactUserId = $this->post('contactUserId');
			$number = $this->post('number');
			$name = $this->post('name');
			$image_data = $this->image_model->do_upload('contact_pic');
		
			$user_data = $this->user_model->addUser($contactUserId,$number,$name,$image_data);
			if(!empty($user_data)){
				if($user_data ==="EXCEED"){
					$this->response(response_fail('FAILED','You can not add more than '.CONTACT_SIZE.' contacts!' ));
				}
			$this->response(response_success($user_data,"SUCCESS", ""));
			}else{
				$this->response(response_fail('FAILED','Contact already exist!' ));
			}
		}else{
			$this->response(response_fail('FAILED',  strip_tags(validation_errors())));
		}
		
	}
	
	/*
	 *send alert for other user
	 *@post
	 */
	public function sendAlert_post(){
		$this->form_validation->set_rules('number','Contact Number','required|max_length[15]|min_length[10]');
		if($this->form_validation->run()==TRUE){
			
			$data = $this->user_model->sendAlert();
			
			if($data){
				$this->response(response_success(array('data' => $data),"Alert sent!!!", ""));
			}else{
				$this->response(response_success(array("FAILED","Message not sent to anyone")));
			}
			
		}else{
				$this->response(response_fail('FAILED',  strip_tags(validation_errors())));
		}
	}
	
	/****
	 *city list
	 *@post
	 */
	public function cityList_post(){
		$data = $this->user_model->get("city");
		$this->response(response_success($data,"SUCCESS", ""));
	}
	
	/***
	 *get venue list by city id
	 *@post
	 */
	public function getVenueList_post()
	{
		$this->form_validation->set_rules('cityID','City Id','required');
		if($this->form_validation->run()==TRUE){
		$cityID = $this->post('cityID');
		$data = $this->user_model->get_venue("venue",array('venueCityId'=>$cityID));
		$this->response(response_success($data,"SUCCESS", ""));
		}else{
				$this->response(response_fail('FAILED',  strip_tags(validation_errors())));
		}
	
	}
	
	/**
	 *alert venue manager
	 *@post
	 */
	public function alertVenueManager_post(){
		$this->form_validation->set_rules('mgrContact','Venue Manager Contact nu.','required');
		$this->form_validation->set_rules('alertText','Alert Text','required');
		if($this->form_validation->run()==TRUE){
			$mgrContact = $this->post('mgrContact');
			$alerttext = $this->post('alertText');
			$data = $this->user_model->alertVenueManager($mgrContact,$alerttext);
			$this->response(response_success($data,"SUCCESS", ""));
		}else{
			$this->response(response_fail('FAILED',  strip_tags(validation_errors())));
		}
	}
	
	/*
	 *delete contacts
	 *@post
	 */
	public function contactDelete_post(){
		$this->form_validation->set_rules('contactId','ContactId','required');
		if($this->form_validation->run()==TRUE){
			$data = $this->user_model->contactDelete();
			if($data){
			$this->response(response_success("Contact deleted","SUCCESS", ""));
			}else{
				$this->response(response_fail('FAILED',"Contact not deleted, Something went wrong."));
			}
		}else{
			$this->response(response_fail('FAILED',  strip_tags(validation_errors())));
		}
		
	}
	
	
	/**
	 *match contact from stk db is registerd or not
	 *@post
	 */
	public function matchContact_post(){
		$this->form_validation->set_rules('contacts','Contact List','required');
		if($this->form_validation->run()==TRUE){
			$data = $this->user_model->matchContact();
			if($data){
			$this->response(response_success($data,"SUCCESS", ""));
			}else{
				$this->response(response_success("No contact found on App.","SUCCESS", ""));
			}
		}else{
			$this->response(response_fail('FAILED',  strip_tags(validation_errors())));
		}
	}
	
	
	/**
	 *deactivate account by userid
	 *@post
	 */
	public function deactivateAccount_post(){
		$this->form_validation->set_rules('userId','User Id','required');
		if($this->form_validation->run()==TRUE){
			$data = $this->user_model->deactivateAccount();
			if($data){
				$this->response(response_success("Account Deactivated","SUCCESS", ""));	
			}else{
				$this->response(response_fail('FAILED',"Something went wrong!"));
			}
		}else{
			$this->response(response_fail('FAILED',  strip_tags(validation_errors())));
		}
	}
	
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */