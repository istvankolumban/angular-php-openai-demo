import { Component, OnInit, ViewChild, ElementRef, AfterViewChecked } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Observable } from 'rxjs';
import { ChatService, ChatMessage, ChatSession } from '../../services/chat.service';
import { AuthService } from '../../services/auth.service';

@Component({
  selector: 'app-chat',
  imports: [CommonModule, FormsModule],
  templateUrl: './chat.component.html',
  styleUrl: './chat.component.scss'
})
export class ChatComponent implements OnInit, AfterViewChecked {
  @ViewChild('messagesContainer') messagesContainer!: ElementRef;

  currentSession$: Observable<ChatSession | null>;
  messages$: Observable<ChatMessage[]>;
  sessions$: Observable<ChatSession[]>;
  
  newMessage = '';
  isLoading = false;

  constructor(
    private chatService: ChatService,
    private authService: AuthService
  ) {
    this.currentSession$ = this.chatService.currentSession$;
    this.messages$ = this.chatService.messages$;
    this.sessions$ = this.chatService.sessions$;
  }

  ngOnInit(): void {
    this.chatService.loadSessions().subscribe();
  }

  ngAfterViewChecked(): void {
    this.scrollToBottom();
  }

  createNewChat(): void {
    this.chatService.createNewSession().subscribe({
      next: (response) => {
        console.log('New chat created:', response);
      },
      error: (error) => {
        console.error('Error creating new chat:', error);
      }
    });
  }

  selectSession(session: ChatSession): void {
    this.chatService.selectSession(session);
  }

  sendMessage(): void {
    if (!this.newMessage.trim() || this.isLoading) {
      return;
    }

    const message = this.newMessage.trim();
    this.newMessage = '';
    this.isLoading = true;

    this.chatService.sendMessage(message).subscribe({
      next: (response) => {
        this.isLoading = false;
        console.log('Message sent successfully:', response);
      },
      error: (error) => {
        this.isLoading = false;
        console.error('Error sending message:', error);
        // You could show an error message to the user here
      }
    });
  }

  logout(): void {
    this.authService.logout();
  }

  private scrollToBottom(): void {
    try {
      if (this.messagesContainer) {
        this.messagesContainer.nativeElement.scrollTop = this.messagesContainer.nativeElement.scrollHeight;
      }
    } catch(err) {
      console.error('Error scrolling to bottom:', err);
    }
  }
}
