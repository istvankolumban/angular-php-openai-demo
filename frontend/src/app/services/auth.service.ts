import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, tap } from 'rxjs';
import { Router } from '@angular/router';
import { ApiService } from './api.service';

export interface User {
  id: number;
  email: string;
  name: string;
  created_at: string;
}

export interface LoginCredentials {
  email: string;
  password: string;
}

export interface RegisterData {
  name: string;
  email: string;
  password: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private currentUserSubject = new BehaviorSubject<User | null>(null);
  private isAuthenticatedSubject = new BehaviorSubject<boolean>(false);
  private authCheckCompleted = new BehaviorSubject<boolean>(false);

  public currentUser$ = this.currentUserSubject.asObservable();
  public isAuthenticated$ = this.isAuthenticatedSubject.asObservable();
  public authCheckCompleted$ = this.authCheckCompleted.asObservable();

  constructor(
    private apiService: ApiService,
    private router: Router
  ) {
    // Check if user is already logged in
    this.checkAuthStatus();
  }

  private checkAuthStatus(): void {
    const token = localStorage.getItem('auth_token');
    if (token) {
      this.apiService.getCurrentUser().subscribe({
        next: (response) => {
          if (response.status === 'success' && response.data) {
            this.currentUserSubject.next(response.data);
            this.isAuthenticatedSubject.next(true);
          } else {
            this.logout();
          }
          this.authCheckCompleted.next(true);
        },
        error: () => {
          this.logout();
          this.authCheckCompleted.next(true);
        }
      });
    } else {
      // No token found
      this.authCheckCompleted.next(true);
    }
  }

  login(credentials: LoginCredentials): Observable<any> {
    return this.apiService.login(credentials).pipe(
      tap(response => {
        if (response.status === 'success' && response.data) {
          localStorage.setItem('auth_token', response.data.token);
          this.currentUserSubject.next(response.data.user);
          this.isAuthenticatedSubject.next(true);
        }
      })
    );
  }

  register(userData: RegisterData): Observable<any> {
    return this.apiService.register(userData).pipe(
      tap(response => {
        if (response.status === 'success' && response.data) {
          localStorage.setItem('auth_token', response.data.token);
          this.currentUserSubject.next(response.data.user);
          this.isAuthenticatedSubject.next(true);
        }
      })
    );
  }

  logout(): void {
    localStorage.removeItem('auth_token');
    this.currentUserSubject.next(null);
    this.isAuthenticatedSubject.next(false);
    this.router.navigate(['/login']);
  }

  getCurrentUser(): User | null {
    return this.currentUserSubject.value;
  }

  isAuthenticated(): boolean {
    return this.isAuthenticatedSubject.value;
  }

  getToken(): string | null {
    return localStorage.getItem('auth_token');
  }
}
