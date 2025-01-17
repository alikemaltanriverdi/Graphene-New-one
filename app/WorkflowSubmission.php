<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowSubmission extends Model
{
    use SoftDeletes;
    protected $fillable = ['workflow_id','workflow_version_id','workflow_instance_id','workflow_instance_configuration','user_id','assignment_type','assignment_id','state','data','status'];
    protected $casts = ['workflow_instance_configuration'=>'object'];
    protected $appends = ['history'];
    protected $hidden = ['history']; // Don't share the full history

    public function workflow() {
        return $this->belongsTo(Workflow::class);
    }
    public function workflowVersion() {
        return $this->belongsTo(WorkflowVersion::class);
    }
    public function workflowInstance() {
        return $this->belongsTo(WorkflowInstance::class);
    }
    public function user() {
        return $this->belongsTo(BulkUser::class,'user_id');
    }
    public function assignment_user() {
        return $this->belongsTo(BulkUser::class,'assignment_id');
    }
    public function assignment_group() {
        return $this->belongsTo(Group::class,'assignment_id');
    }
    public function logs() {
        return $this->hasMany(WorkflowActivityLog::class);
    }
    public function files() {
        return $this->hasMany(WorkflowSubmissionFile::class);
    }

    public function getAssignment() {
        if($this->assignment_type == "user"){
            $this->assignee = User::where("id", '=', $this->assignment_id)->first()->only('first_name','last_name','email','unique_id','params');
        }else{
            $this->assignee = Group::where("id",'=',$this->assignment_id)->where('site_id',config('app.site')->id)->select('name','slug','id')->first();
        }
    }
    public function getSubmittedAt() {
        $activity = WorkflowActivityLog::select('created_at')
            ->where('workflow_submission_id',$this->id)
            ->orderBy('id','asc')->first();
        if (!is_null($activity)) {
            $this->submitted_at=$activity->created_at->format('Y-m-d H:i:s');
        } else {
            $this->submitted_at = null;
        }
    }
    public function getDataAttribute($value) {
        $payload = json_decode($value,true);
        if (isset($payload['_encrypted']) && $payload['_encrypted']===true && isset($payload['data'])) {
            return json_decode(decrypt($payload['data']),true);
        } else {
            return $payload;
        }
    }
    public function setDataAttribute($value) {
        $instance = WorkflowInstance::where('id',$this->workflow_instance_id)->select('configuration')->first();
        if (isset($instance->configuration->encrypted) && ($instance->configuration->encrypted === true)) {
            $this->attributes['data'] = json_encode(['_encrypted'=>true,'data'=>encrypt(json_encode($value))]);
        } else {
            $this->attributes['data'] = json_encode($value);
        }
    }

    public function getHistoryAttribute() {
        $activity_log = WorkflowActivityLog::where('workflow_submission_id',$this->id)->get();
        $history = [];
        foreach($activity_log as $activity) {
            $history[] = [
                'id'=>$activity->id,
                'data'=>$activity->data,
                'action'=>$activity->action,
                'comment'=>$activity->comment,
                'state'=>$activity->end_state,
                'status'=>$activity->status,
                'assignment'=>[
                    'id'=>$activity->assignment_id,
                    'type'=>$activity->assignment_type,
                ],
                'actor'=> [
                    'id' =>$activity->user_id,
                ],
                'previous' => [
                    'state' => $activity->start_state,
                ]
            ];
        }
        return $history;
    }
}