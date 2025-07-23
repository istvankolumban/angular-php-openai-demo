import { Injectable } from '@angular/core';
import { Observable, BehaviorSubject } from 'rxjs';
import { ApiService } from './api.service';

export interface ChatMessage {
  id?: number;
  content: string;
  role: 'user' | 'assistant';
  timestamp: Date;
  session_id?: number;
}

export interface ChatSession {
  id: number;
  name: string;
  created_at: string;
  message_count?: number;
}

@Injectable({
  providedIn: 'root'
})
export class ChatService {
  private currentSessionSubject = new BehaviorSubject<ChatSession | null>(null);
  private messagesSubject = new BehaviorSubject<ChatMessage[]>([]);
  private sessionsSubject = new BehaviorSubject<ChatSession[]>([]);

  public currentSession$ = this.currentSessionSubject.asObservable();
  public messages$ = this.messagesSubject.asObservable();
  public sessions$ = this.sessionsSubject.asObservable();

  constructor(private apiService: ApiService) {
    this.loadSessions();
  }

  loadSessions(): Observable<any> {
    return new Observable(observer => {
      this.apiService.getChatSessions().subscribe({
        next: (response) => {
          if (response.status === 'success' && response.data) {
            this.sessionsSubject.next(response.data);
          }
          observer.next(response);
          observer.complete();
        },
        error: (error) => {
          observer.error(error);
        }
      });
    });
  }

  createNewSession(name?: string): Observable<any> {
    const sessionName = name || `Chat ${new Date().toLocaleDateString()}`;
    
    return new Observable(observer => {
      this.apiService.createChatSession(sessionName).subscribe({
        next: (response) => {
          if (response.status === 'success' && response.data) {
            const newSession: ChatSession = {
              id: response.data.id,
              name: response.data.title || sessionName,
              created_at: response.data.created_at
            };
            
            this.currentSessionSubject.next(newSession);
            this.messagesSubject.next([]);
            
            // Add to sessions list
            const currentSessions = this.sessionsSubject.value;
            this.sessionsSubject.next([newSession, ...currentSessions]);
            
            observer.next({ status: 'success', data: newSession });
            observer.complete();
          } else {
            observer.error(response.message || 'Failed to create session');
          }
        },
        error: (error) => {
          console.error('Error creating session:', error);
          observer.error(error);
        }
      });
    });
  }

  selectSession(session: ChatSession): void {
    this.currentSessionSubject.next(session);
    this.loadMessages(session.id);
  }

  loadMessages(sessionId: number): void {
    this.apiService.getMessages(sessionId).subscribe({
      next: (response) => {
        if (response.status === 'success' && response.data) {
          const messages = response.data.map((msg: any) => ({
            ...msg,
            timestamp: new Date(msg.timestamp || msg.created_at)
          }));
          this.messagesSubject.next(messages);
        }
      },
      error: (error) => {
        console.error('Error loading messages:', error);
        this.messagesSubject.next([]);
      }
    });
  }

  sendMessage(content: string): Observable<any> {
    const currentSession = this.currentSessionSubject.value;
    if (!currentSession) {
      throw new Error('No active chat session');
    }

    // Add user message immediately to UI
    const userMessage: ChatMessage = {
      content,
      role: 'user',
      timestamp: new Date(),
      session_id: currentSession.id
    };

    const currentMessages = this.messagesSubject.value;
    this.messagesSubject.next([...currentMessages, userMessage]);

    // Send to backend
    const messageData = {
      session_id: currentSession.id,
      message: content
    };

    return new Observable(observer => {
      this.apiService.sendMessage(messageData).subscribe({
        next: (response) => {
          if (response.status === 'success' && response.data) {
            // Add assistant response to messages
            const assistantMessage: ChatMessage = {
              content: response.data.response,
              role: 'assistant',
              timestamp: new Date(),
              session_id: currentSession.id
            };

            const updatedMessages = this.messagesSubject.value;
            this.messagesSubject.next([...updatedMessages, assistantMessage]);
          }
          observer.next(response);
          observer.complete();
        },
        error: (error) => {
          console.error('Error sending message:', error);
          observer.error(error);
        }
      });
    });
  }

  getCurrentSession(): ChatSession | null {
    return this.currentSessionSubject.value;
  }

  getMessages(): ChatMessage[] {
    return this.messagesSubject.value;
  }
}
