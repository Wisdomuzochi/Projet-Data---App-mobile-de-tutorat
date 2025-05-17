class AuthService {
  static const String _userKey = 'current_user';
  static const String _tokenKey = 'auth_token';
  
  final Dio _dio;
  late final SharedPreferences _prefs;
  Utilisateur? _currentUser;
  String? _token;

  AuthService(this._dio, this._prefs);

  static Future<AuthService> defaultConstructor() async {
    final prefs = await SharedPreferences.getInstance();
    return AuthService(Dio(), prefs);
  }

  // ... existing code ...
} 