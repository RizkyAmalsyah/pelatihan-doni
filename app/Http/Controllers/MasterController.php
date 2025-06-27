<?php

namespace App\Http\Controllers;

use App\Exports\RegisterExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;

use App\Models\User;
use App\Models\Category;
use App\Models\Vector;
use App\Models\Training;
use App\Models\TrainingVector;
use App\Models\RegisTraining;
use App\Models\Banner;
use Maatwebsite\Excel\Facades\Excel;

class MasterController extends Controller
{

  // GET VIEW
  public function user()
  {
    // SET TITLE
    $data['title'] = 'Master Users';
    $data['subtitle'] = 'Users management';

    return view('admin.master.user', $data);
  }

  public function admin()
  {
    // SET TITLE
    $data['title'] = 'Master Admins';
    $data['subtitle'] = 'Admins management';

    return view('admin.master.admin', $data);
  }

  public function category()
  {
    // SET TITLE
    $data['title'] = 'Master Category';
    $data['subtitle'] = 'Category management';

    return view('admin.master.category', $data);
  }


  public function vector()
  {
    // SET TITLE
    $data['title'] = 'Master Vector';
    $data['subtitle'] = 'Vector management';

    return view('admin.master.vector', $data);
  }


  public function training()
  {
    // SET TITLE
    $data['title'] = 'Master Training';
    $data['subtitle'] = 'Training management';

    // GET DATA
    $category = Category::where('status', 'Y')->get();
    $vector = Vector::where('status', 'Y')->get();

    // SET DATA
    $data['category'] = $category;
    $data['vector'] = $vector;

    return view('admin.master.training', $data);
  }

  public function banner()
  {
    // SET TITLE
    $data['title'] = 'Master Banner';
    $data['subtitle'] = 'Banner management';

    return view('admin.master.banner', $data);
  }

  public function single_training(Request $request)
  {
    $id = $request->input('id');

    $result = Training::find($id);

    $vector = TrainingVector::where('id_training', $id)->get();

    $vt = [];
    if ($vector) {
      foreach ($vector as $key) {
        $vt[] = $key->id_vector;
      }
    }
    $data['result'] = $result;
    $data['vector'] = $vt;
    return response()->json($data);
  }


  // POST FUNCTION


  // // USER
  public function insert_user(Request $request)
  {
    $arrVar = [
      'name' => 'Full name',
      'email' => 'Email address',
      'phone' => 'Phone number',
      'password' => 'Password',
      'repassword' => 'Password confirmation',
      'role' => 'Peran'
    ];

    $post = [];
    $arrAccess = [];
    $data = [];

    foreach ($arrVar as $var => $value) {
      $$var = $request->input($var);
      if (!$$var) {
        $data['required'][] = ['req_' . $var, "$value cannot be empty!"];
        $arrAccess[] = false;
      } else {
        if (!in_array($var, ['password', 'repassword'])) {
          $post[$var] = trim($$var);
          $arrAccess[] = true;
        }
      }
    }

    if (in_array(false, $arrAccess)) {
      return response()->json(['status' => false, 'required' => $data['required']]);
    }

    $tujuan = public_path('data/user/');
    if (!File::exists($tujuan)) {
      File::makeDirectory($tujuan, 0755, true, true);
    }
    if ($request->hasFile('image')) {
      $image = $request->file('image');
      $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
      $image->move($tujuan, $fileName);

      $post['image'] = $fileName;
    }

    if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
      return response()->json([
        'status' => 700,
        'alert' => ['message' => 'Invalid email address!']
      ]);
    }

    if (User::where('email', $request->email)->where('deleted', 'N')->exists()) {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Email address is already registered!']
      ]);
    }

    if ($request->password !== $request->repassword) {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Password confirmation does not match!']
      ]);
    }

    $prefix = config('session.prefix');
    $id_user = session($prefix . '_id_user');

    $page = 'user';
    if ($role == 1) {
      $page = "admin";
    }

    $post['password'] = $request->password;
    $post['created_by'] = $id_user;

    $insert = User::create($post);

    if ($insert) {
      return response()->json([
        'status' => true,
        'alert' => ['message' => 'Data added successfully!'],
        'datatable' => 'table_' . $page,
        'modal' => ['id' => '#kt_modal_' . $page, 'action' => 'hide'],
        'input' => ['all' => true]
      ]);
    } else {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Failed to add data!']
      ]);
    }
  }

  public function update_user(Request $request)
  {
    $id = $request->id_user;
    $user = User::where('id_user', $id)->where('deleted', 'N')->first();

    if (!$user) {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'User not found!']
      ]);
    }

    $arrVar = [
      'name' => 'Full name',
      'phone' => 'Phone number',
      'email' => 'Email address',
      'role' => 'Peran'
    ];

    $post = [];
    $arrAccess = [];
    $data = [];

    foreach ($arrVar as $var => $value) {
      $$var = $request->input($var);
      if (!$$var) {
        $data['required'][] = ['req_' . $var, "$value cannot be empty!"];
        $arrAccess[] = false;
      } else {
        $post[$var] = trim($$var);
        $arrAccess[] = true;
      }
    }

    if (in_array(false, $arrAccess)) {
      return response()->json(['status' => false, 'required' => $data['required']]);
    }
    // Cek duplikat email (exclude current user)
    if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
      return response()->json([
        'status' => 700,
        'alert' => ['message' => 'Invalid email address!']
      ]);
    }

    if (User::where('email', $request->email)->where('id_user', '!=', $id)->where('deleted', 'N')->exists()) {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Email address is already used by another user!']
      ]);
    }

    // Jika password diisi, validasi dan hash
    if ($request->filled('password')) {
      if ($request->password !== $request->repassword) {
        return response()->json([
          'status' => false,
          'alert' => ['message' => 'Password confirmation does not match!']
        ]);
      }
      $post['password'] = $request->password;
    }

    $tujuan = public_path('data/user/');
    $name_image = $request->name_image;
    if (!File::exists($tujuan)) {
      File::makeDirectory($tujuan, 0755, true, true);
    }
    if ($request->hasFile('image')) {
      $image = $request->file('image');
      $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
      $image->move($tujuan, $fileName);

      if ($user->image && file_exists($tujuan . $user->image)) {
        unlink($tujuan . $user->image);
      }

      $post['image'] = $fileName;
    } elseif (!$name_image) {
      if ($user->image && file_exists($tujuan . $user->image)) {
        unlink($tujuan . $user->image);
      }
      $post['image'] = null;
    }

    $page = 'user';
    if ($role == 1) {
      $page = "admin";
    }

    $update = $user->update($post);

    if ($update) {
      return response()->json([
        'status' => true,
        'alert' => ['message' => 'updated successfully!'],
        'datatable' => 'table_' . $page,
        'modal' => ['id' => '#kt_modal_' . $page, 'action' => 'hide'],
        'input' => ['all' => true]
      ]);
    } else {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Failed to update!']
      ]);
    }


    return response()->json(['status' => false]);
  }


  // // CATEGORY
  public function insert_category(Request $request)
  {
    $arrVar = [
      'name' => 'Category',
    ];

    $post = [];
    $arrAccess = [];
    $data = [];

    foreach ($arrVar as $var => $value) {
      $$var = $request->input($var);
      if (!$$var) {
        $data['required'][] = ['req_' . $var, "$value cannot be empty!"];
        $arrAccess[] = false;
      } else {
        $post[$var] = trim($$var);
        $arrAccess[] = true;
      }
    }

    if (in_array(false, $arrAccess)) {
      return response()->json(['status' => false, 'required' => $data['required']]);
    }

    $prefix = config('session.prefix');
    $id_user = session($prefix . '_id_user');

    $post['created_by'] = $id_user;

    $insert = Category::create($post);

    if ($insert) {
      return response()->json([
        'status' => true,
        'alert' => ['message' => 'Data added successfully!'],
        'datatable' => 'table_category',
        'modal' => ['id' => '#kt_modal_category', 'action' => 'hide'],
        'input' => ['all' => true]
      ]);
    } else {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Failed to add data!']
      ]);
    }
  }

  public function update_category(Request $request)
  {
    $id = $request->id_category;
    $category = Category::where('id_category', $id)->where('deleted', 'N')->first();

    if (!$category) {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Category not found!']
      ]);
    }

    $arrVar = [
      'name' => 'Category'
    ];

    $post = [];
    $arrAccess = [];
    $data = [];

    foreach ($arrVar as $var => $value) {
      $$var = $request->input($var);
      if (!$$var) {
        $data['required'][] = ['req_' . $var, "$value cannot be empty!"];
        $arrAccess[] = false;
      } else {
        $post[$var] = trim($$var);
        $arrAccess[] = true;
      }
    }

    if (in_array(false, $arrAccess)) {
      return response()->json(['status' => false, 'required' => $data['required']]);
    }

    $update = $category->update($post);

    if ($update) {
      return response()->json([
        'status' => true,
        'alert' => ['message' => 'updated successfully!'],
        'datatable' => 'table_category',
        'modal' => ['id' => '#kt_modal_category', 'action' => 'hide'],
        'input' => ['all' => true]
      ]);
    } else {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Failed to update!']
      ]);
    }


    return response()->json(['status' => false]);
  }




  // // VECTOR

  public function insert_vector(Request $request)
  {
    $arrVar = [
      'name' => 'Vector',
    ];

    $post = [];
    $arrAccess = [];
    $data = [];

    foreach ($arrVar as $var => $value) {
      $$var = $request->input($var);
      if (!$$var) {
        $data['required'][] = ['req_' . $var, "$value cannot be empty!"];
        $arrAccess[] = false;
      } else {
        $post[$var] = trim($$var);
        $arrAccess[] = true;
      }
    }

    if (in_array(false, $arrAccess)) {
      return response()->json(['status' => false, 'required' => $data['required']]);
    }

    $prefix = config('session.prefix');
    $id_user = session($prefix . '_id_user');

    $post['created_by'] = $id_user;

    $insert = Vector::create($post);

    if ($insert) {
      return response()->json([
        'status' => true,
        'alert' => ['message' => 'Data added successfully!'],
        'datatable' => 'table_vector',
        'modal' => ['id' => '#kt_modal_vector', 'action' => 'hide'],
        'input' => ['all' => true]
      ]);
    } else {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Failed to add data!']
      ]);
    }
  }

  public function update_vector(Request $request)
  {
    $id = $request->id_vector;
    $vector = Vector::where('id_vector', $id)->where('deleted', 'N')->first();

    if (!$vector) {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Vector not found!']
      ]);
    }

    $arrVar = [
      'name' => 'Vector'
    ];

    $post = [];
    $arrAccess = [];
    $data = [];

    foreach ($arrVar as $var => $value) {
      $$var = $request->input($var);
      if (!$$var) {
        $data['required'][] = ['req_' . $var, "$value cannot be empty!"];
        $arrAccess[] = false;
      } else {
        $post[$var] = trim($$var);
        $arrAccess[] = true;
      }
    }

    if (in_array(false, $arrAccess)) {
      return response()->json(['status' => false, 'required' => $data['required']]);
    }

    $update = $vector->update($post);

    if ($update) {
      return response()->json([
        'status' => true,
        'alert' => ['message' => 'updated successfully!'],
        'datatable' => 'table_vector',
        'modal' => ['id' => '#kt_modal_vector', 'action' => 'hide'],
        'input' => ['all' => true]
      ]);
    } else {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Failed to update!']
      ]);
    }


    return response()->json(['status' => false]);
  }



  // // TRAINING
  public function insert_training(Request $request)
  {
    $arrVar = [
      'title' => 'Judul',
      'id_category' => 'Kategori',
      'description' => 'Deskripsi',
      'sort_description' => 'Deskripsi singkat'
    ];

    $post = [];
    $arrAccess = [];
    $data = [];

    foreach ($arrVar as $var => $value) {
      $$var = $request->input($var);
      if (!$$var) {
        $data['required'][] = ['req_' . $var, "$value cannot be empty!"];
        $arrAccess[] = false;
      } else {
        if (in_array($var, ['description'])) {
          $cc = ckeditor_check($$var);
          if (empty($cc)) {
            $data['required'][] = ['req_' . $var, $value . " tidak boleh kosong!"];
            $arrAccess[] = false;
          } else {
            $post[$var] = $$var;
            $arrAccess[] = true;
          }
        } else {
          if ($$var === '') {
            $data['required'][] = ['req_' . $var, $value . " tidak boleh kosong!"];
            $arrAccess[] = false;
          } else {
            $post[$var] = $$var;
            $arrAccess[] = true;
          }
        }
      }
    }

    if (in_array(false, $arrAccess)) {
      return response()->json(['status' => false, 'required' => $data['required']]);
    }

    $tujuan = public_path('data/training/');
    if (!File::exists($tujuan)) {
      File::makeDirectory($tujuan, 0755, true, true);
    }
    if ($request->hasFile('image')) {
      $image = $request->file('image');
      $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
      $image->move($tujuan, $fileName);

      $post['image'] = $fileName;
    } else {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Image cannot be null!']
      ]);
    }
    $prefix = config('session.prefix');
    $id_user = session($prefix . '_id_user');

    $post['created_by'] = $id_user;

    $insert = Training::create($post);

    if ($insert) {
      $vector = $request->input('vector');

      if ($vector) {
        $set = [];
        $no = 0;
        foreach ($vector as $key) {
          $num = $no++;
          $set[$num]['id_training'] = $insert->id_training;
          $set[$num]['id_vector'] = $key;
        }
        TrainingVector::insert($set);
      }
      return response()->json([
        'status' => true,
        'alert' => ['message' => 'Data added successfully!'],
        'datatable' => 'table_training',
        'modal' => ['id' => '#kt_modal_training', 'action' => 'hide'],
        'input' => ['all' => true]
      ]);
    } else {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Failed to add data!']
      ]);
    }
  }

  public function update_training(Request $request)
  {
    $id = $request->id_training;
    $training = Training::where('id_training', $id)->where('deleted', 'N')->first();

    if (!$training) {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'training not found!']
      ]);
    }

    $arrVar = [
      'title' => 'Judul',
      'id_category' => 'Kategori',
      'description' => 'Deskripsi',
      'sort_description' => 'Deskripsi singkat'
    ];

    $post = [];
    $arrAccess = [];
    $data = [];

    foreach ($arrVar as $var => $value) {
      $$var = $request->input($var);
      if (!$$var) {
        $data['required'][] = ['req_' . $var, "$value cannot be empty!"];
        $arrAccess[] = false;
      } else {
        $post[$var] = trim($$var);
        $arrAccess[] = true;
      }
    }

    if (in_array(false, $arrAccess)) {
      return response()->json(['status' => false, 'required' => $data['required']]);
    }

    $tujuan = public_path('data/training/');
    $name_image = $request->name_image;
    if (!File::exists($tujuan)) {
      File::makeDirectory($tujuan, 0755, true, true);
    }
    if ($request->hasFile('image')) {
      $image = $request->file('image');
      $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
      $image->move($tujuan, $fileName);

      if ($training->image && file_exists($tujuan . $training->image)) {
        unlink($tujuan . $training->image);
      }

      $post['image'] = $fileName;
    } elseif (!$name_image) {
      if ($training->image && file_exists($tujuan . $training->image)) {
        return response()->json([
          'status' => false,
          'alert' => ['message' => 'Image cannot be null!']
        ]);
      }
    }


    $update = $training->update($post);

    if ($update) {
      $vector = $request->input('vector');
      TrainingVector::where('id_training', $id)->delete();
      if ($vector) {
        $set = [];
        $no = 0;
        foreach ($vector as $key) {
          $num = $no++;
          $set[$num]['id_training'] = $id;
          $set[$num]['id_vector'] = $key;
        }
        TrainingVector::insert($set);
      }
      return response()->json([
        'status' => true,
        'alert' => ['message' => 'updated successfully!'],
        'datatable' => 'table_training',
        'modal' => ['id' => '#kt_modal_training', 'action' => 'hide'],
        'input' => ['all' => true]
      ]);
    } else {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Failed to update!']
      ]);
    }


    return response()->json(['status' => false]);
  }

  public function modal_register(Request $request)
  {
    $id = $request->id;
    $result = RegisTraining::with(['user'])->where('id_training', $id)->where('approved', 'Y')->get();
    $data['result'] = $result;

    return view('admin.master.modal.register', $data);
  }

  public function download_register(Request $request)
  {
    $id = $request->id;
    $training = Training::where('id_training', $id)->firstOrFail();
    $filename = 'Daftar_Peserta_' . str_replace(' ', '_', $training->title) . '.xlsx';

    return Excel::download(new RegisterExport($id), $filename);
  }

  public function delete_regis(Request $request)
  {
    $id = $request->id;

    if (!$id) {
      return response()->json(['status' => false, 'message' => 'ID not found']);
    }

    $cek = RegisTraining::find($id);

    if (!$cek) {
      return response()->json(['status' => false, 'message' => 'Data not found']);
    }

    try {
      $count = RegisTraining::where('id_training', $cek->id_training)->where('approved', 'Y')->count();
      $cek->delete();
      $final = $count - 1;
      return response()->json(['status' => true, 'message' => 'Data deleted successfully', 'count' => $final]);
    } catch (\Exception $e) {
      return response()->json(['status' => false, 'message' => 'Failed to delete data']);
    }
  }



  // // BANNER
  public function insert_banner(Request $request)
  {
    $arrVar = [
      'title' => 'Judul',
      'description' => 'Deskripsi'
    ];

    $post = [];
    $arrAccess = [];
    $data = [];

    foreach ($arrVar as $var => $value) {
      $$var = $request->input($var);
      if (!$$var) {
        $data['required'][] = ['req_' . $var, "$value cannot be empty!"];
        $arrAccess[] = false;
      } else {
        if (in_array($var, ['description'])) {
          $cc = ckeditor_check($$var);
          if (empty($cc)) {
            $data['required'][] = ['req_' . $var, $value . " tidak boleh kosong!"];
            $arrAccess[] = false;
          } else {
            $post[$var] = $$var;
            $arrAccess[] = true;
          }
        } else {
          if ($$var === '') {
            $data['required'][] = ['req_' . $var, $value . " tidak boleh kosong!"];
            $arrAccess[] = false;
          } else {
            $post[$var] = $$var;
            $arrAccess[] = true;
          }
        }
      }
    }

    if (in_array(false, $arrAccess)) {
      return response()->json(['status' => false, 'required' => $data['required']]);
    }

    $tujuan = public_path('data/banner/');
    if (!File::exists($tujuan)) {
      File::makeDirectory($tujuan, 0755, true, true);
    }
    if ($request->hasFile('image')) {
      $image = $request->file('image');
      $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
      $image->move($tujuan, $fileName);

      $post['image'] = $fileName;
    } else {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Image cannot be null!']
      ]);
    }
    $prefix = config('session.prefix');
    $id_user = session($prefix . '_id_user');

    $post['created_by'] = $id_user;

    $insert = Banner::create($post);

    if ($insert) {
      return response()->json([
        'status' => true,
        'alert' => ['message' => 'Data added successfully!'],
        'datatable' => 'table_banner',
        'modal' => ['id' => '#kt_modal_banner', 'action' => 'hide'],
        'input' => ['all' => true]
      ]);
    } else {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Failed to add data!']
      ]);
    }
  }

  public function update_banner(Request $request)
  {
    $id = $request->id_banner;
    $banner = Banner::where('id_banner', $id)->where('deleted', 'N')->first();

    if (!$banner) {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'banner not found!']
      ]);
    }

    $arrVar = [
      'title' => 'Judul',
      'description' => 'Deskripsi'
    ];

    $post = [];
    $arrAccess = [];
    $data = [];

    foreach ($arrVar as $var => $value) {
      $$var = $request->input($var);
      if (!$$var) {
        $data['required'][] = ['req_' . $var, "$value cannot be empty!"];
        $arrAccess[] = false;
      } else {
        $post[$var] = trim($$var);
        $arrAccess[] = true;
      }
    }

    if (in_array(false, $arrAccess)) {
      return response()->json(['status' => false, 'required' => $data['required']]);
    }

    $tujuan = public_path('data/banner/');
    $name_image = $request->name_image;
    if (!File::exists($tujuan)) {
      File::makeDirectory($tujuan, 0755, true, true);
    }
    if ($request->hasFile('image')) {
      $image = $request->file('image');
      $fileName = uniqid() . '.' . $image->getClientOriginalExtension();
      $image->move($tujuan, $fileName);

      if ($banner->image && file_exists($tujuan . $banner->image)) {
        unlink($tujuan . $banner->image);
      }

      $post['image'] = $fileName;
    } elseif (!$name_image) {
      if ($banner->image && file_exists($tujuan . $banner->image)) {
        return response()->json([
          'status' => false,
          'alert' => ['message' => 'Image cannot be null!']
        ]);
      }
    }


    $update = $banner->update($post);

    if ($update) {
      return response()->json([
        'status' => true,
        'alert' => ['message' => 'updated successfully!'],
        'datatable' => 'table_banner',
        'modal' => ['id' => '#kt_modal_banner', 'action' => 'hide'],
        'input' => ['all' => true]
      ]);
    } else {
      return response()->json([
        'status' => false,
        'alert' => ['message' => 'Failed to update!']
      ]);
    }


    return response()->json(['status' => false]);
  }
}
