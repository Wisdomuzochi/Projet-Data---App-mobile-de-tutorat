void main() async {
  WidgetsFlutterBinding.ensureInitialized();
  
  // Configuration de l'URL de base pour l'API
  ApiService.configureBaseUrl('http://172.20.10.7/backend_php'); // Pour l'émulateur Android

  // Initialisation du service d'authentification
  final authService = await AuthService.defaultConstructor();

  runApp(MyApp(authService: authService));
}

class MyApp extends StatelessWidget {
  final AuthService authService;
  
  const MyApp({Key? key, required this.authService}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        ChangeNotifierProvider(create: (_) => AuthProvider(authService)),
      ],
      child: MaterialApp(
        title: 'Flutter Demo',
        theme: ThemeData(
          primarySwatch: Colors.blue,
        ),
        home: HomeScreen(),
      ),
    );
  }
} 