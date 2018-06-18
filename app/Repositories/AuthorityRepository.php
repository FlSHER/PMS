<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use App\Models\AuthorityGroup;
use App\Models\AuthorityGroupHasDepartment;
use App\Models\AuthorityGroupHasStaff;
use Illuminate\Database\Eloquent\Model;

class AuthorityRepository
{
    use Traits\Filterable;

    protected $authModel;
    protected $departmentModel;
    protected $staffModel;

    public function __construct(AuthorityGroup $authModel,
                                AuthorityGroupHasDepartment $departmentModel,
                                AuthorityGroupHasStaff $staffModel)
    {
        $this->authModel = $authModel;
        $this->departmentModel = $departmentModel;
        $this->staffModel = $staffModel;
    }

    /**
     * @param Request $request
     * @return array
     *  todo 待大改   用filters 方式筛选关联   前面是model.fields;
     */
    public function getAuthGroupList(Request $request):array
    {
        $builder = ($this->authModel instanceof Model) ? $this->authModel->query() : $this->authModel;
        $department = $request->query('department_id');
        $staff_id = $request->query('staff_id');

        $builder->when($department, function ($query) use ($department) {
            $query->whereHas('department', function ($query) use ($department) {
                $query->whereIn('department_id', $department);
            });
        });
        $builder->when($staff_id, function ($query) use ($staff_id) {
            $query->whereHas('staff', function ($query) use ($staff_id) {
                $query->where('staff_sn', $staff_id);
            });
        });
        $items = $builder->with('department')->with('staff')->paginate();
        return [
            'data' => $items->items(),
            'total' => $items->count(),
            'page' => $items->currentPage(),
            'pagesize' => $items->perPage(),
            'totalpage' => $items->total(),
        ];
    }

    public function firstAuthGroup($name)
    {
        return $this->authModel->where('name',$name)->first();
    }

    public function addAuthority($request)
    {
        $auth=$this->authModel;
        $auth->name=$request->name;
        $auth->save();
        return $auth->id;
    }

    public function addStaff($data)
    {
        $this->staffModel->authority_group_id=$data['authority_group_id'];
        $this->staffModel->staff_sn=$data['staff_sn'];
        $this->staffModel->staff_name=$data['staff_name'];
        return $this->staffModel->save();
    }

    public function addDepartment($departmentData)
    {
        $this->departmentModel->authority_group_id=$departmentData['group_id'];
        $this->departmentModel->department_id=$departmentData['department_id'];
        $this->departmentModel->department_name=$departmentData['department_full_name'];
        return $this->departmentModel->save();
    }

    public function getIdAuthGroup($id)
    {
        return $this->authModel->where('id',$id)->with('departmentMany')->with('staffMany')->get();
    }

    public function staffOnly($id,$staff)
    {
        return $this->staffModel->where('authority_group_id',$id)->where('staff_sn',$staff)->first();
    }

    public function departmentOnly($id,$department)
    {
        return $this->departmentModel->where('authority_group_id',$id)->where('department_id',$department)->first();
    }

    public function updateFirstAuthGroup($request)
    {
        return $this->authModel->whereNotIn('id',explode(',',$request->route('id')))->where('name',$request->name)->first();
    }
    public function editAuthGroup($request)
    {
        $authModel = $this->authModel->find($request->route('id'));
        if (empty($authModel)) {
            abort(404,'未找到原始数据');
        }
        return $authModel->update($request->all());
    }

    public function editStaffGroup($request)
    {
        $staff=$this->staffModel->where('authority_group_id',$request->route('id'))->where('staff_sn',$request->old_staff_sn)->first();
        if($staff == null){
            abort(404,'找不到当前数据');
        }
        $this->staffModel->authority_group_id=$request->route('id');
        $this->staffModel->staff_sn=$request->staff_sn;
        $this->staffModel->staff_name=$request->staff_name;
        $bool=$this->staffModel->save();
        if(true==(bool)$bool){
            return $staff->where('authority_group_id',$request->route('id'))->where('staff_sn',$request->old_staff_sn)->delete();
        };
    }

    public function editDepartmentGroup($request)
    {
        $department=$this->departmentModel->where('authority_group_id',$request->route('id'))->where('department_id',$request->old_department_id)->first();
        if($department == null){
            abort(404,'找不到当前数据');
        }
        $this->departmentModel->authority_group_id=$request->route('id');
        $this->departmentModel->department_id=$request->department_id;
        $this->departmentModel->department_full_name=$request->department_full_name;
        $bool=$this->departmentModel->save();
        if(true==(bool)$bool){
            return $department->where('authority_group_id',$request->route('id'))->where('department_id',$request->old_department_id)->delete();
        };
    }
}