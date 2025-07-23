import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface ApiResponse<T = any> {
  status: string;
  message: string;
  data?: T;
  error?: string;
}

@Injectable({
  providedIn: 'root'
})
export class ApiService {
  private baseUrl = environment.apiUrl;

  constructor(private http: HttpClient) { }

  private getHeaders(): HttpHeaders {
    const token = localStorage.getItem('auth_token');
    return new HttpHeaders({
      'Content-Type': 'application/json',
      ...(token && { 'Authorization': `Bearer ${token}` })
    });
  }

  // Auth endpoints
  register(userData: any): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(`${this.baseUrl}/auth/register.php`, userData, {
      headers: this.getHeaders()
    });
  }

  login(credentials: any): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(`${this.baseUrl}/auth/login.php`, credentials, {
      headers: this.getHeaders()
    });
  }

  getCurrentUser(): Observable<ApiResponse> {
    return this.http.get<ApiResponse>(`${this.baseUrl}/auth/me.php`, {
      headers: this.getHeaders()
    });
  }

  // Chat endpoints
  getChatSessions(): Observable<ApiResponse> {
    return this.http.get<ApiResponse>(`${this.baseUrl}/chat/sessions.php`, {
      headers: this.getHeaders()
    });
  }

  createChatSession(title: string): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(`${this.baseUrl}/chat/sessions.php`, { title }, {
      headers: this.getHeaders()
    });
  }

  sendMessage(messageData: any): Observable<ApiResponse> {
    return this.http.post<ApiResponse>(`${this.baseUrl}/chat/message.php`, messageData, {
      headers: this.getHeaders()
    });
  }

  getMessages(sessionId: number): Observable<ApiResponse> {
    return this.http.get<ApiResponse>(`${this.baseUrl}/chat/messages.php?session_id=${sessionId}`, {
      headers: this.getHeaders()
    });
  }

  // Usage tracking
  getUsageStats(): Observable<ApiResponse> {
    return this.http.get<ApiResponse>(`${this.baseUrl}/usage.php`, {
      headers: this.getHeaders()
    });
  }
}
