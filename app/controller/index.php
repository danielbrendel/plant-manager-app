<?php

/*
    Asatru PHP - Example controller

    Add here all your needed routes implementations related to 'index'.
*/

/**
 * Index controller
 */
class IndexController extends BaseController {
	const INDEX_LAYOUT = 'layout';

	/**
	 * Perform base initialization
	 * 
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct(self::INDEX_LAYOUT);
	}

	/**
	 * Handles URL: /
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function index($request)
	{
		$user = UserModel::getAuthUser();
		$locs = LocationsModel::getAll();
		$warning_plants = PlantsModel::getWarningPlants();
		$overdue_tasks = TasksModel::getOverdueTasks();
		$log = LogModel::getHistory();
		$stats = UtilsModule::getStats();

		if ($user->get('show_plants_aoru')) {
			$last_plants_list = PlantsModel::getLastAddedPlants();
		} else {
			$last_plants_list = PlantsModel::getLastAuthoredPlants();
		}
		
		return parent::view(['content', 'index'], [
			'user' => $user,
			'warning_plants' => $warning_plants,
			'overdue_tasks' => $overdue_tasks,
			'locations' => $locs,
			'log' => $log,
			'stats' => $stats,
			'last_plants_list' => $last_plants_list
		]);
	}

	/**
	 * Handles URL: /auth
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function auth($request)
	{
		$view = new Asatru\View\ViewHandler();
		$view->setLayout('auth');

		return $view;
	}

	/**
	 * Handles URL: /login
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function login($request)
	{
		try {
			$email = $request->params()->query('email', null);
			$password = $request->params()->query('password', null);
			
			UserModel::login($email, $password);

			return redirect('/');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /logout
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function logout($request)
	{
		try {
			UserModel::logout();

			return redirect('/');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /password/restore
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function restore_password($request)
	{
		try {
			$email = $request->params()->query('email', null);

			UserModel::restorePassword($email);

			FlashMessage::setMsg('success', __('app.restore_password_info'));

			return redirect('/');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /password/reset
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function view_reset_password($request)
	{
		$token = $request->params()->query('token');

		return view('pwreset', [], ['token' => $token]);
	}

	/**
	 * Handles URL: /password/reset
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function reset_password($request)
	{
		try {
			$token = $request->params()->query('token', null);
			$password = $request->params()->query('password', null);
			$password_confirmation = $request->params()->query('password_confirmation', null);

			if ($password !== $password_confirmation) {
				throw new \Exception(__('app.password_mismatch'));
			}

			UserModel::resetPassword($token, $password);

			return redirect('/');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /tasks
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function view_tasks($request)
	{
		$user = UserModel::getAuthUser();

		$done = $request->params()->query('done', false);

		$tasks = TasksModel::getTasks($done);

		return parent::view(['content', 'tasks'], [
			'user' => $user,
			'tasks' => $tasks
		]);
	}

	/**
	 * Handles URL: /tasks/create
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function create_task($request)
	{
		$validator = new Asatru\Controller\PostValidator([
			'title' => 'required',
			'description' => 'required'
		]);

		if (!$validator->isValid()) {
			$errorstr = '';
			foreach ($validator->errorMsgs() as $err) {
				$errorstr .= $err . '<br/>';
			}

			FlashMessage::setMsg('error', 'Invalid data given:<br/>' . $errorstr);
			
			return back();
		}

		$title = $request->params()->query('title', null);
		$description = $request->params()->query('description', '');
		$due_date = $request->params()->query('due_date', '');

		if (strlen($due_date) === 0) {
			$due_date = null;
		}

		TasksModel::addTask($title, $description, $due_date);

		FlashMessage::setMsg('success', __('app.task_created_successfully'));

		return redirect('/tasks');
	}

	/**
	 * Handles URL: /tasks/edit
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function edit_task($request)
	{
		$validator = new Asatru\Controller\PostValidator([
			'task' => 'required',
			'title' => 'required',
			'description' => 'required'
		]);

		if (!$validator->isValid()) {
			$errorstr = '';
			foreach ($validator->errorMsgs() as $err) {
				$errorstr .= $err . '<br/>';
			}

			FlashMessage::setMsg('error', 'Invalid data given:<br/>' . $errorstr);
			
			return back();
		}

		$task = $request->params()->query('task', null);
		$title = $request->params()->query('title', null);
		$description = $request->params()->query('description', '');
		$due_date = $request->params()->query('due_date', '');

		if (strlen($due_date) === 0) {
			$due_date = null;
		}

		TasksModel::editTask($task, $title, $description, $due_date);

		FlashMessage::setMsg('success', __('app.task_edited_successfully'));

		return redirect('/tasks#task-anchor-' . $task);
	}

	/**
	 * Handles URL: /tasks/toggle
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function toggle_task($request)
	{
		try {
			$task = $request->params()->query('task', null);

			TasksModel::toggleTaskStatus($task);

			return json([
				'code' => 200
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler
	 */
	public function view_inventory($request)
	{
		$user = UserModel::getAuthUser();

		$inventory = InventoryModel::getInventory();

		$expand = $request->params()->query('expand', null);
		
		return parent::view(['content', 'inventory'], [
			'user' => $user,
			'inventory' => $inventory,
			'_expand_inventory_item' => $expand
		]);
	}

	/**
	 * Handles URL: /inventory/add
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_inventory_item($request)
	{
		$validator = new Asatru\Controller\PostValidator([
			'name' => 'required',
			'group' => 'required'
		]);

		if (!$validator->isValid()) {
			$errorstr = '';
			foreach ($validator->errorMsgs() as $err) {
				$errorstr .= $err . '<br/>';
			}

			FlashMessage::setMsg('error', 'Invalid data given:<br/>' . $errorstr);
			
			return back();
		}

		$name = $request->params()->query('name', null);
		$group = $request->params()->query('group', null);
		$description = $request->params()->query('description', null);

		$id = InventoryModel::addItem($name, $description, $group);

		return redirect('/inventory?expand=' . $id . '#anchor-item-' . $id);
	}

	/**
	 * Handles URL: /inventory/edit
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function edit_inventory_item($request)
	{
		$validator = new Asatru\Controller\PostValidator([
			'id' => 'required',
			'name' => 'required',
			'group' => 'required'
		]);

		if (!$validator->isValid()) {
			$errorstr = '';
			foreach ($validator->errorMsgs() as $err) {
				$errorstr .= $err . '<br/>';
			}

			FlashMessage::setMsg('error', 'Invalid data given:<br/>' . $errorstr);
			
			return back();
		}

		$id = $request->params()->query('id', null);
		$name = $request->params()->query('name', null);
		$group = $request->params()->query('group', null);
		$description = $request->params()->query('description', null);

		InventoryModel::editItem($id, $name, $description, $group);

		return redirect('/inventory?expand=' . $id . '#anchor-item-' . $id);
	}

	/**
	 * Handles URL: /inventory/amount/increment
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function inc_inventory_item($request)
	{
		try {
			$id = $request->params()->query('id', null);

			$amount = InventoryModel::incAmount($id);

			return json([
				'code' => 200,
				'amount' => $amount
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory/amount/decrement
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function dec_inventory_item($request)
	{
		try {
			$id = $request->params()->query('id', null);

			$amount = InventoryModel::decAmount($id);

			return json([
				'code' => 200,
				'amount' => $amount
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function remove_inventory_item($request)
	{
		try {
			$id = $request->params()->query('id', null);

			InventoryModel::removeItem($id);

			return json([
				'code' => 200
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory/group/add
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_inventory_group_item($request)
	{
		$validator = new Asatru\Controller\PostValidator([
			'token' => 'required',
			'label' => 'required'
		]);

		if (!$validator->isValid()) {
			$errorstr = '';
			foreach ($validator->errorMsgs() as $err) {
				$errorstr .= $err . '<br/>';
			}

			FlashMessage::setMsg('error', 'Invalid data given:<br/>' . $errorstr);
			
			return back();
		}

		$token = $request->params()->query('token', null);
		$label = $request->params()->query('label', null);

		try {
			InvGroupModel::addItem($token, $label);
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}

		return redirect('/inventory');
	}

	/**
	 * Handles URL: /inventory/group/edit
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function edit_inventory_group_item($request)
	{
		try {
			$id = $request->params()->query('id', null);
			$what = $request->params()->query('what', null);
			$value = $request->params()->query('value', null);

			InvGroupModel::editItem($id, $what, $value);

			return json([
				'code' => 200
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /inventory/group/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function remove_inventory_group_item($request)
	{
		try {
			$id = $request->params()->query('id', null);

			InvGroupModel::removeItem($id);

			return json([
				'code' => 200
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /chat
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler|Asatru\View\RedirectHandler
	 */
	public function view_chat($request)
	{
		if (!env('APP_ENABLECHAT')) {
			return redirect('/');
		}

		$user = UserModel::getAuthUser();

		$messages = ChatMsgModel::getChat();

		return parent::view(['content', 'chat'], [
			'user' => $user,
			'messages' => $messages,
			'_refresh_chat' => true
		]);
	}

	/**
	 * Handles URL: /chat/add
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_chat_message($request)
	{
		$validator = new Asatru\Controller\PostValidator([
			'message' => 'required'
		]);

		if (!$validator->isValid()) {
			$errorstr = '';
			foreach ($validator->errorMsgs() as $err) {
				$errorstr .= $err . '<br/>';
			}

			FlashMessage::setMsg('error', 'Invalid data given:<br/>' . $errorstr);
			
			return back();
		}

		$message = $request->params()->query('message', null);

		ChatMsgModel::addMessage($message);

		return redirect('/chat');
	}

	/**
	 * Handles URL: /chat/query
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function query_chat_messages($request)
	{
		try {
			$result = [];

			$messages = ChatMsgModel::getLatestMessages();

			foreach ($messages as $message) {
				$result[] = [
					'id' => $message->get('id'),
					'userId' => $message->get('userId'),
					'userName' => UserModel::getNameById($message->get('userId')),
					'message' => $message->get('message'),
					'chatcolor' => UserModel::getChatColorForUser($message->get('userId')),
					'created_at' => $message->get('created_at'),
					'diffForHumans' => (new Carbon($message->get('created_at')))->diffForHumans(),
				];
			}

			return json([
				'code' => 200,
				'messages' => $result
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /chat/typing/update
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function update_chat_typing($request)
	{
		try {
			UserModel::updateChatTyping();
			
			return json([
				'code' => 200
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /chat/typing
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function get_chat_typing_status($request)
	{
		try {
			$status = UserModel::isAnyoneTypingInChat();

			return json([
				'code' => 200,
				'status' => $status
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /user/online
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\JsonHandler
	 */
	public function get_online_users($request)
	{
		try {
			$result = [];

			$users = UserModel::getOnlineUsers();

			foreach ($users as $user) {
				$result[] = [
					'name' => $user->get('name'),
					'typing' => UtilsModule::isTyping($user->get('last_typing'))
				];
			}

			return json([
				'code' => 200,
				'users' => $result
			]);
		} catch (\Exception $e) {
			return json([
				'code' => 500,
				'msg' => $e->getMessage()
			]);
		}
	}

	/**
	 * Handles URL: /history
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\ViewHandler|Asatru\View\RedirectHandler
	 */
	public function view_history($request)
	{
		if (!env('APP_ENABLEHISTORY')) {
			return redirect('/');
		}

		$year = $request->params()->query('year', null);
		$limit = $request->params()->query('limit', null);
		$sorting = $request->params()->query('sorting', null);
		$direction = $request->params()->query('direction', null);

		$user = UserModel::getAuthUser();

		$years = PlantsModel::getHistoryYears();
		$history = PlantsModel::getHistory($year, $limit, $sorting, $direction);

		return parent::view(['content', 'history'], [
			'user' => $user,
			'history' => $history,
			'years' => $years,
			'sorting_types' => PlantsModel::$sorting_list,
			'sorting_dirs' => PlantsModel::$sorting_dir
		]);
	}

	/**
	 * Handles URL: /plants/history/add
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function add_to_history($request)
	{
		try {
			$plant = $request->params()->query('plant', null);

			PlantsModel::markHistorical($plant);

			return redirect('/history');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}

	/**
	 * Handles URL: /plants/history/remove
	 * 
	 * @param Asatru\Controller\ControllerArg $request
	 * @return Asatru\View\RedirectHandler
	 */
	public function remove_from_history($request)
	{
		try {
			$plant = $request->params()->query('plant', null);

			PlantsModel::unmarkHistorical($plant);

			return redirect('/history');
		} catch (\Exception $e) {
			FlashMessage::setMsg('error', $e->getMessage());
			return back();
		}
	}
}
