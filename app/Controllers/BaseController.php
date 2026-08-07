<?php

namespace App\Controllers;

use App\Models\AppSettingModel;
use App\Models\NotificationModel;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

abstract class BaseController extends Controller
{
    protected $request;
    protected $helpers = ['url', 'compliance', 'period', 'role', 'access'];
    protected bool $isWritable = false;
    protected string $role = 'viewer';
    protected int $notifCount = 0;
    protected array $notifications = [];
    protected array $appSettings = [];
    protected string $defaultTitle = 'Dashboard';
    private const NOTIF_CACHE_TTL = 300;

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->role = session()->get('role') ?? 'viewer';
        $this->isWritable = hasWriteAccess() && in_array($this->role, ['admin', 'compliance'], true);
        if (db_connect()->tableExists('app_settings')) $this->appSettings = (new AppSettingModel())->allAsMap();
        if (session()->get('logged_in')) { $this->markOpenedNotification(); $this->loadNotifications(); }
        $this->defaultTitle = $this->resolveDefaultTitle();
        foreach (['defaultTitle'=>$this->defaultTitle,'notifCount'=>$this->notifCount,'notifications'=>$this->notifications,'appSettings'=>$this->appSettings] as $key=>$value) service('renderer')->setVar($key,$value);
    }

    private function markOpenedNotification(): void
    {
        $notificationId=(int)$this->request->getGet('notification_id');$userId=(int)session()->get('user_id');
        if($notificationId<1||$userId<1||!db_connect()->tableExists('notifications'))return;
        $model=new NotificationModel();$row=$model->where('id',$notificationId)->where('user_id',$userId)->first();if($row&&empty($row['read_at']))$model->update($notificationId,['read_at'=>date('Y-m-d H:i:s')]);
    }

    private function loadNotifications(): void
    {
        $userId=(int)session()->get('user_id');
        if($userId>0&&db_connect()->tableExists('notifications')){$model=new NotificationModel();$this->notifCount=$model->unreadCount($userId);foreach($model->unreadForUser($userId,6)as$item)$this->notifications[]=['icon'=>$this->notificationIcon((string)($item['type']??'info')),'text'=>(string)($item['title']??'Notifikasi').' — '.(string)($item['message']??''),'url'=>$this->notificationUrl((string)($item['url']??'/home'),(int)$item['id'])];}
        $cacheKey='sidebar_notif_'.$userId;$cached=cache()->get($cacheKey);if(!is_array($cached)){$cached=$this->calculateChecklistReminders($userId);cache()->save($cacheKey,$cached,self::NOTIF_CACHE_TTL);} $pending=(int)($cached['pending']??0);$late=(int)($cached['late']??0);$this->notifCount+=$pending;
        if($pending>0)$this->notifications[]=['icon'=>'bi bi-clock text-warning','text'=>$pending.' periode belum checklist','url'=>base_url('home?show=all')];if($late>0)$this->notifications[]=['icon'=>'bi bi-exclamation-triangle text-danger','text'=>$late.' periode sudah terlambat','url'=>base_url('home?show=all')];
    }

    private function calculateChecklistReminders(int $userId): array
    {
        if($userId<1)return['pending'=>0,'late'=>0];$inventoryModel=new \App\Models\ComplianceInventoryModel();$logModel=new \App\Models\ChecklistLogModel();
        $inventories=$inventoryModel->select('compliance_inventory.id, asset_item_types.checklist_frequency')->join('asset_item_types','asset_item_types.id = compliance_inventory.item_type_id')->assignedToUser($userId)->findAll();
        $targets=[];$periodKeys=[];foreach($inventories as$inventory){$frequency=$inventory['checklist_frequency']??null;if(!$frequency)continue;$periodKey=generate_period_key($frequency);$targets[]=['id'=>(int)$inventory['id'],'frequency'=>$frequency,'periodKey'=>$periodKey];$periodKeys[$periodKey]=true;}if($targets===[])return['pending'=>0,'late'=>0];
        $logs=$logModel->select('inventory_id, period_key')->whereIn('inventory_id',array_column($targets,'id'))->whereIn('period_key',array_keys($periodKeys))->findAll();$done=[];foreach($logs as$log)$done[$log['inventory_id'].'|'.$log['period_key']]=true;$pending=0;$late=0;foreach($targets as$target){if(isset($done[$target['id'].'|'.$target['periodKey']]))continue;$pending++;if(is_period_late($target['frequency'],$target['periodKey']))$late++;}return compact('pending','late');
    }

    private function notificationIcon(string $type): string { return match($type){'assignment'=>'bi bi-person-check text-primary','reminder'=>'bi bi-alarm text-warning','comment'=>'bi bi-chat-left-text text-info','approval'=>'bi bi-patch-check text-success',default=>'bi bi-bell text-primary'}; }
    private function notificationUrl(string $url,int $id): string { $absolute=preg_match('#^https?://#i',$url)?$url:base_url(ltrim($url,'/'));return $absolute.(str_contains($absolute,'?')?'&':'?').'notification_id='.$id; }
    protected function render(string $view,array $data=[]){$data['defaultTitle']=$data['defaultTitle']??$this->defaultTitle;$data['isWritable']=$this->isWritable;$data['role']=$this->role;$data['notifCount']=$this->notifCount;$data['notifications']=$this->notifications;$data['appSettings']=$this->appSettings;return view($view,$data);}
    protected function resolveDefaultTitle(): string {$router=service('router');$controller=$router->controllerName();$method=$router->methodName();if(!is_string($controller)||$controller==='')$controller=static::class;$parts=explode('\\',$controller);$short=end($parts)?:'';$short=preg_replace('/Controller$/','',$short)??$short;$short=str_replace(['_','-'],' ',$short);$short=preg_replace('/(?<=[a-z])(?=[A-Z])/',' ',$short)??$short;$short=trim(preg_replace('/\s+/',' ',$short)??$short);if($short===''||strtolower($short)==='base')return'Dashboard';$methodText='';if(is_string($method)&&$method!==''&&strtolower($method)!=='index'){$methodText=str_replace(['_','-'],' ',$method);$methodText=preg_replace('/(?<=[a-z])(?=[A-Z])/',' ',$methodText)??$methodText;}return trim($short.' '.$methodText);}
}
