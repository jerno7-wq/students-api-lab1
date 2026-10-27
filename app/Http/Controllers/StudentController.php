<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    // GET /api/students  -> show all students
    public function index()
    {
        return response()->json(Student::all(), 200);
    }

    // POST /api/students  -> create a student
    public function store(Request $request)
    {
        $data = $request->validate([
            'student_number' => 'required|string|unique:students,student_number',
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'email'          => 'required|email|unique:students,email',
            'course'         => 'required|string|max:100',
            'year_level'     => 'required|integer|between:1,5',
        ]);

        $student = Student::create($data);

        return response()->json($student, 201);
    }

    // GET /api/students/{id}  -> show one student
    public function show($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        return response()->json($student, 200);
    }

    // PUT /api/students/{id}  -> update a student
    public function update(Request $request, $id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $data = $request->validate([
            'student_number' => 'required|string|unique:students,student_number,' . $id,
            'first_name'     => 'required|string|max:100',
            'last_name'      => 'required|string|max:100',
            'email'          => 'required|email|unique:students,email,' . $id,
            'course'         => 'required|string|max:100',
            'year_level'     => 'required|integer|between:1,5',
        ]);

        $student->update($data);

        return response()->json($student, 200);
    }

    // DELETE /api/students/{id}  -> delete a student
    public function destroy($id)
    {
        $student = Student::find($id);

        if (!$student) {
            return response()->json(['message' => 'Student not found'], 404);
        }

        $student->delete();

        return response()->json(['message' => 'Student deleted successfully'], 200);
    }
}