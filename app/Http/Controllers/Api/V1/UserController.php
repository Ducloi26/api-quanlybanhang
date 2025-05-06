<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class UserController extends Controller
{
    public function updateAvatar(Request $request)
    {
        // Validate file avatar
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:10240', // Tối đa 10MB
        ]);

        // Lấy user đang đăng nhập
        $user = $request->user();

        // Xóa avatar cũ nếu tồn tại
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Lưu file avatar mới
        $path = $request->file('avatar')->store('avatars', 'public');

        // Cập nhật đường dẫn avatar trong database
        $user->update([
            'avatar' => $path,
        ]);

        // Trả về phản hồi JSON
        return response()->json([
            'message' => 'Avatar updated successfully',
            'avatar_url' => Storage::url($path),
        ], 200);
    }

    public function updateProfile(Request $request)
    {
        // Validate dữ liệu đầu vào
        $request->validate([
            'username' => 'sometimes|string|max:255|unique:users,username,' . $request->user()->id,
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        // Lấy user đang đăng nhập
        $user = $request->user();

        // Cập nhật thông tin
        $user->update([
            'username' => $request->input('username', $user->username),
            'name' => $request->input('name', $user->name),
            'email' => $request->input('email', $user->email),
        ]);

        // Trả về phản hồi JSON
        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user,
        ], 200);
    }
    public function deleteMyAccount(Request $request)
    {
        // Lấy user đang đăng nhập
        $user = $request->user();

        // Xóa file avatar nếu tồn tại
        if ($user->avatar) {
            $avatarPath = public_path($user->avatar);
            if (file_exists($avatarPath)) {
                unlink($avatarPath);
            }
        }

        // Thu hồi token (logout user)
        $user->tokens()->delete(); // Xóa tất cả token của user (Laravel Passport)

        // Xóa user
        $user->delete();

        // Trả về phản hồi JSON
        return response()->json([
            'message' => 'Account deleted successfully',
        ], 200);
    }

    public function getProfiles(Request $request)
    {
        // Validate dữ liệu đầu vào (nếu có user_id trong payload)
        $request->validate([
            'user_id' => 'sometimes|integer|exists:users,id',
        ]);

        // Lấy user đang đăng nhập
        $currentUser = $request->user();

        // Nếu không gửi user_id, trả về profile của user hiện tại
        if (!$request->has('user_id')) {
            return response()->json([
                'message' => 'Profile retrieved successfully',
                'profile' => $currentUser,
            ], 200);
        }

        // Nếu có user_id, lấy profile của user khác
        $userId = $request->input('user_id');
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'message' => 'User not found',
            ], 404);
        }

        // Kiểm tra quyền (nếu cần, ví dụ chỉ admin mới được xem profile của user khác)
        // Giả sử bạn có trường role trong bảng users
        if ($currentUser->role !== 'admin') {
            return response()->json([
                'message' => 'Unauthorized to view other user profiles',
            ], 403);
        }

        return response()->json([
            'message' => 'Profile retrieved successfully',
            'profile' => $user,
        ], 200);
    }

    public function search(Request $request)
    {
        // Validate query parameter
        $request->validate([
            'query' => 'required|string|min:1|max:255',
        ]);

        // Lấy tham số query
        $query = $request->query('query');

        // Tìm kiếm user
        $users = User::where('username', 'LIKE', "%{$query}%")
            ->orWhere('name', 'LIKE', "%{$query}%")
            ->orWhere('email', 'LIKE', "%{$query}%")
            ->get();

        // Kiểm tra nếu không tìm thấy user
        if ($users->isEmpty()) {
            return response()->json([
                'message' => 'No users found',
                'users' => [],
            ], 200);
        }

        // Trả về danh sách user
        return response()->json([
            'message' => 'Users found successfully',
            'users' => $users,
        ], 200);
    }
}