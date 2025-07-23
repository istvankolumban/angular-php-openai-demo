import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, Validators, ReactiveFormsModule } from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { AuthService, LoginCredentials } from '../../../services/auth.service';

@Component({
  selector: 'app-login',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterModule
  ],
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss'
})
export class LoginComponent {
  loginForm: FormGroup;
  isLoading = false;
  errorMessage = '';

  constructor(
    private fb: FormBuilder,
    private authService: AuthService,
    private router: Router
  ) {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required, Validators.minLength(6)]]
    });
  }

  onSubmit(): void {
    if (this.loginForm.valid && !this.isLoading) {
      this.isLoading = true;
      this.errorMessage = '';
      const credentials: LoginCredentials = this.loginForm.value;

      // Debug: Log the data being sent
      console.log('Login credentials:', credentials);

      this.authService.login(credentials).subscribe({
        next: (response) => {
          this.isLoading = false;
          console.log('Login response:', response);
          console.log('Response status:', response.status);
          console.log('Status check result:', response.status === 'success');
          
          if (response.status === 'success') {
            console.log('Redirecting to /chat...');
            this.router.navigate(['/chat']).then(success => {
              console.log('Navigation success:', success);
            }).catch(error => {
              console.error('Navigation error:', error);
            });
          } else {
            console.log('Login failed, showing error message');
            this.errorMessage = response.message || 'Login failed';
          }
        },
        error: (error) => {
          this.isLoading = false;
          console.error('Login error:', error);
          this.errorMessage = 'Login failed. Please try again.';
        }
      });
    }
  }
}
